<?php

namespace App\Components;

/**
 * Class simBaseAuth
 * @package App\Components
 * @property SimbaseApiRequest $simBaseApi
 */
class simBaseAuth
{

    private $simBaseApi;

    public function __construct()
    {
        $simBaseApi_host = env('SIMBASE_URL');
        $simBaseApi_interface_id_hex = env('SIMBASE_ID');

//        $is_https = true;
//        $port = 443; // default: 80 for HTTP, 443 for HTTPS
//        $urn = '/api/';

        $is_https = true;
        $port     = null; // default: 80 for HTTP, 443 for HTTPS
        $urn      = '/';


        $simBaseApi = new SimbaseApiRequest($simBaseApi_host, $is_https, $port, $urn);

        $simBaseApi->connect();

        $simBaseApi_user_login = env('SIMBASE_USER');
        $simBaseApi_user_password = env('SIMBASE_PASS');

        $simBaseApi->authDataSet($simBaseApi_interface_id_hex, $simBaseApi_user_login, $simBaseApi_user_password, $_SERVER['REMOTE_ADDR']);

        $this->simBaseApi = $simBaseApi;
    }

    public function callFunction(string $name = '', array $fields = [])
    {
        $request = '<function name="' . $name . '">';

        foreach ($fields as $key => $item) {
            $request .= '<arg name="' . $key . '">' . $this->simBaseApi->xmlEscape($item) . '</arg>';
        }

        $request .= '</function>';

        return $this->parseResponse(
            $this->simBaseApi->sendRequest(5000, $request)
        );
    }

    public function createObject(int $process, int $group, array $fields = [])
    {

        $request = '<object process="' . $process . '" group="' . $group . '">';

        foreach ($fields as $key => $item) {
            $request .= '<field name="' . $key . '">' . $this->simBaseApi->xmlEscape($item) . '</field>';
        }

        $request .= '</object>';

        return $this->parseResponse(
            $this->simBaseApi->sendRequest(3000, $request)
        );
    }

    public function updateObject(int $object_id, array $fields = [], $message_type = 3030, array $attr = [])
    {

        array_walk($attr, function (&$v, $k) {
            $v = $k . '="' . $v . '"';
        });

        $request = '<object id="' . $object_id . '" ' . implode(' ', $attr) . '>';

        foreach ($fields as $key => $item) {
            $request .= '<field name="' . $key . '">' . $this->simBaseApi->xmlEscape($item) . '</field>';
        }

        $request .= '</object>';

        return $this->parseResponse(
            $this->simBaseApi->sendRequest($message_type, $request)
        );
    }

    public function findObject(array $search = [], array $fields = [], array $files = [], $dictionary = null, $limit = 1000000, $total = 'on')
    {

        $request = '<search' . ($dictionary ? ' dictionary="' . $dictionary . '"' : '') . '>' .
            implode("\n", array_map(function ($item) {
                return '<field name="' . $this->simBaseApi->xmlEscape($item['name']) . '" operator="' . $this->simBaseApi->xmlEscape($item['operator']) . '" ' . (isset($item['value']) ? 'value="' . $this->simBaseApi->xmlEscape($item['value']) . '"' : '') . ' />';
            }, $search)) .
            '</search>';

        $request .= '<data limit="' . $limit . '" total="' . $total . '">' .
            implode("\n", array_map(function ($item) {
                return '<field name="' . $this->simBaseApi->xmlEscape($item) . '" />';
            }, $fields)) .
            implode("\n", array_map(function ($item) {
                return '<file name="' . $this->simBaseApi->xmlEscape($item['name']) . '" ' . (isset($item['size']) ? 'size="' . json_encode($item['size']) . '"' : '') . ' />';
            }, $files)) .
            '</data>';

        return $this->parseResponse(
            $this->simBaseApi->sendRequest($dictionary ? 3100 : 3020, $request)
        );
    }

    private function parseResponse(string $response): array
    {
        $items = [];

        $response = simplexml_load_string($response);

        if (isset($response->body->object)) {
            $items = $this->_object_attr($response->body->object);
        }

        if (isset($response->body->data->object)) {
            foreach ($response->body->data->object ?? [] as $r) {
                $items[] = $this->_object_attr($r);
            }
        }

        if (isset($response->body->data->element)) {
            foreach ($response->body->data->element ?? [] as $r) {
                $items[] = $this->_object_attr($r);
            }
        }

        if (isset($response->header->error)) {
            $error = trim((string)$response->header->error->attributes()['text']);

            if (stripos($error, '* ERRORS:') !== false) {
                $error = explode('* ERRORS:', (string)$error);
                $error = array_pop($error);
            }

            if (!empty($error)) {
                $items['errors'] = [trim($error)];
            }
        }

        if (!count($items) && is_countable($response->body) && count($response->body) == 1) {
            $items['body'] = trim((string)$response->body);

            if (is_array(json_decode($items['body'], 1))) {
                $items = json_decode($items['body'], 1);
            }

            if (empty($items['body']) || $items['body'] == 'Object not found') {
                unset($items['body']);
            }
        }

        return $items;
    }

    private function _object_attr(\SimpleXMLElement $r)
    {

        $attr = [
            'id' => (int)$r->attributes()['id']
        ];

        if (isset($r->file)) {
            $files = [];
            foreach ($r->file as $file) {
                $files[] = [
                    'name' => (string)$file->attributes()['name'],
                    'content' => (string)$file
                ];
            }
            $attr['attachments'] = $files;
        }

        foreach ($r->field as $field) {
            $name = (string)$field->attributes()['name'];
            $attr[$name] = (string)$field;
        }

        return $attr;
    }
}
