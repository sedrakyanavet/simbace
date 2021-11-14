<?php

namespace App\Components;

class SimbaseApiRequest
{
    const API_VERSION = 8;

    const PORT_HTTP  = 80;
    const PORT_HTTPS = 443;


    protected $is_https;
    protected $host;
    protected $urn = '/';
    protected $port;
    protected $connection_timeout = 20;
    protected $connection_resource = null;

    protected $interface_id;
    protected $user_login;
    protected $user_password;
    protected $user_password_type;
    protected $user_ip;


    //* @var array - массив строк - для доп. информации в логе */
    protected $tracing = array();

    /**
     *
     */
    public function __construct($host, bool $is_https = false, $port = null, $urn = '/')
    {
        $this->is_https = $is_https;
        $this->host     = $host;
        $this->urn      = $urn;

        if ($port == null) {
            if ($this->is_https)
                $port = self::PORT_HTTPS;
            else
                $port = self::PORT_HTTP;
        }

        $this->port = $port;
    }

    /**
     * Подключение к серверу, где находиться API
     */
    public function connect()
    {

        $msg = sprintf(
            'Соединяемся с "%s://%s%s" (порт=%s)',
            ($this->is_https ? 'https' : 'http'),
            $this->host,
            $this->urn,
            $this->port
        );

        $this->tracingAddInfo($msg);

//        $connection_resource = fsockopen(
//            sprintf(
//                '%s://%s',
//                ($this->is_https ? 'ssl' : 'tcp'),
//                $this->host
//            ),
//            $this->port,
//            $errno,
//            $errstr,
//            $this->connection_timeout
//        );

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false
            ]
        ]);

        $hostname = sprintf(
            '%s://%s:%s',
            ($this->is_https ? 'tls' : 'tcp'),
            $this->host,
            $this->port
        );

        $connection_resource = stream_socket_client($hostname, $errno, $errstr, $this->connection_timeout, STREAM_CLIENT_CONNECT, $context);

        if ($connection_resource) {
            $this->tracingAddInfo('Соединение с сервером установлено');
            $this->connection_resource = $connection_resource;
            return true;
        } else {
            $this->tracingAddError(sprintf('Не удалось соединиться (errno=%s; errstr=%s)', $errno, $errstr));
            return false;
        }
    }


    /**
     * установить параметры пользователя через которого мы работаем в системе
     * @param string $interface_id (ИД интерфейса API в HEX-формате, зарегистированого в системе SimBASE)
     * @param string $password_type - 'open' или 'hash'
     */
    public function authDataSet($interface_id, $login, $password, $ip, $password_type = 'open')
    {
        $this->interface_id       = hexdec($interface_id);
        $this->user_login         = $login;
        $this->user_password      = $password;
        $this->user_password_type = $password_type;
        $this->user_ip            = $ip;
    }


    /**
     * Отправить сообщение
     * @param int $message_type - тип API-сообщения
     * @param string body       - все что нужно вставить внуть тега <body>
     * @return string|false
     */
    public function sendRequest($message_type, $body)
    {
        $xml = $this->messageFullXml($message_type, $body);

        if(env('APP_DEBUG', false)){
            file_put_contents(storage_path('logs') . '/request-' . md5($body) . '.xml', $xml);
        }

        if (!$this->sendHttpRequest($xml) ){
            return false;
        }

        $response = $this->readResponse();

        if(stripos($response, '<body>{') !== false){
            $response = str_replace(['<body>{', '}</body>'], ['<body><![CDATA[{', '}]]></body>'], $response);
        }

        if(env('APP_DEBUG', false)) {
            file_put_contents(storage_path('logs') . '/response-' . md5($body) . '.xml', $response);
            file_put_contents(storage_path('logs') . '/tracing-' . md5($body) . '.log', implode(PHP_EOL, $this->tracing));
        }

        return $response;
    }


    /**
     * экранировать спецсимволы для XML
     * (исопльзуется для жкранирования значений аттрибутов и текста внутри тегов)
     * @param $str
     * @param bool $cdata
     * @return array|mixed|string
     */
    public function xmlEscape($str, $cdata = false){

        if( is_array($str) ){
            $keys = array_keys($str);
            $ci = count($keys);
            for( $i = 0; $i < $ci; $i++ ){
                $str[$keys[$i]] = $this->xmlEscape($str[$keys[$i]], $cdata);
            }
            return $str;
        }

        if($cdata) return '<![CDATA[' . $str . ']]>';

        return str_replace(
            array('&',     '<',    '>',    "'",      '"'),
            array('&amp;', '&lt;', '&gt;', '&apos;', '&quot;'),
            $str
        );
    }


    /**
     * вернуть трассировочные данные
     */
    public function tracingAsString()
    {
        return implode("\n", $this->tracing);
    }



    /**
     * сформировать XML всего сообщения
     */
    protected function messageFullXml($message_type, $body)
    {
        $xml  = '<?xml version="1.0" encoding="UTF-8" ?>' . "\n";
        $xml .= '<sbapi>' . "\n";
        $xml .= $this->messageHeader($message_type);
        $xml .= '<body>' . $body . '</body>' . "\n";
        $xml .= '</sbapi>';

        //echo $xml; exit;

        return $xml;
    }

    /**
     * сформировать заголовок сообщения
     */
    protected function messageHeader($message_type)
    {
        $message_id = 1;
        // $message_id = $this->messageId();
        $created    = date('Y-m-d') . 'T' . date('H:i:s') . 'z';
        $auth_data  = $this->authData($message_id, $message_type);

        $xml  = '<header>' . "\n";
        $xml .=     '<interface id="' . $this->interface_id . '" version="' . self::API_VERSION . '" />' . "\n";
        $xml .=     '<message id="' . $message_id . '" ignore_id="yes" type="' . $message_type . '" created="' . $created . '" />' . "\n";
        $xml .=     '<error id="0" text="" />' . "\n";
        $xml .=     '<auth pwd="' .  $this->user_password_type . '">' . $auth_data . '</auth>' . "\n";
        $xml .= '</header>' . "\n";

        return $xml;
    }


    /**
     * получить последнее значение message_id
     */
    protected function readLastMessageIdGet()
    {
        try{
            $file_pathname   = $this->messageIdFile();
            $last_message_id = !file_exists($file_pathname) ? intval(microtime(true) * 1000) :  file_get_contents($file_pathname) + 1;
            file_put_contents($file_pathname, $last_message_id);
            return (int) $last_message_id;
        } catch (\Exception $e){
            sleep(1);
            return $this->readLastMessageIdGet();
        }
    }

    /**
     * имя файла где храниться message_id запроса к API
     */
    protected function messageIdFile()
    {
        // для каждой системы создается отдельный файл!
        $file_basename = sprintf('api_message_id_%s.txt', strtoupper(dechex($this->interface_id)));
        $file_pathname = sprintf('%s/%s', __DIR__, $file_basename);
        return $file_pathname;
    }


    /**
     * параметры аутентификации
     */
    protected function authData($message_id, $message_type)
    {
        $xml = sprintf(
            '<authdata user="%s" password="%s" msg_id="%s" msg_type="%s" user_ip="%s" />',
            $this->xmlEscape($this->user_login),
            $this->xmlEscape($this->user_password),
            $this->xmlEscape($message_id),
            $this->xmlEscape($message_type),
            $this->xmlEscape($this->user_ip)
        );

        $base64 = base64_encode($xml);

        return $base64;
    }


    /**
     * Отправить HTTP-запрос
     * @return bool
     */
    protected function sendHttpRequest($xml)
    {
        $this->tracingAddInfo('');
        $this->tracingAddInfo('ЗАПРОС: '. "\n\n" . $this->xmlBeautify($xml) . "\n");

        // формируем
        $http_request =  $this->httpMessage($xml);

        // отправляем
        $bytes_sent = fwrite($this->connection_resource, $http_request);

        return ( $bytes_sent > 0 );
    }

    /**
     * Сформировать HTTP-запрос
     * @return string
     */
    protected function httpMessage($xml)
    {
        $request  = sprintf("POST %s HTTP/1.1\r\n", $this->urn);
        $request .= sprintf("Host: %s\r\n", $this->host);
        $request .= "Connection: keep-alive\r\n";
        $request .= "User-Agent: API\r\n";
        $request .= "Content-type: text/xml\r\n";
        $request .= "Content-Length: ". strlen($xml) ."\r\n";
        $request .= "\r\n";
        $request .= $xml;

        return $request;
    }


    /**
     * ИД сообщения
     * (не должен повторяться, со стороны клиентской-системы нужно обеспечивать уникальность номера,
     * каждый номер должен быть больше предыдущего, т.к. все номера меньше чем последний полученный
     * считаются недопустимыми)
     */
    protected function messageId()
    {
        // // ВАЖНО:
        // //   такой способ удобен для демонстрации или тестов,
        // //   но не подходит, если в одну секунду может быть более одного сообщения.
        // //   для живых задач лучше использовать блок который закоментирован ниже.
        // return intval(microtime(true) * 1000);

        return $this->readLastMessageIdGet();
    }


    /**
     * Прочесть ответное HTTP-сообщение, получить тело сообщения
     * @return string|false
     */
    protected function readResponse(){

        $response = '';

        $chunked = false;
        $len = NULL;

        $connection_resource = $this->connection_resource;


        // чтение HTTP-загловков
        while( !feof($connection_resource) ){

            $buf = fgets($connection_resource);

            if( preg_match('/Content-Length:\s+([0-9]+)\s*/i', $buf, $matches) ){
                $len = $matches[1];
            }
            elseif( preg_match('/Transfer-Encoding:\s+chunked\s*/i', $buf, $matches) ){
                $chunked = true;
            }
            // HTTP-заголовки кончились
            elseif( $buf === "\r\n" ){

                break;
            }
        }

        // чтение тела сообщения
        if( $chunked ){

            while( true ){

                $len = fgets($connection_resource);
                if( $len === false ) return false;

                // convert HEX --> DEC
                $len = hexdec(trim($len));
                if( $len == 0 ){

                    // read separator between chunked blocks (\r\n)
                    // WARNING: this operation is very important, otherwise we will not read
                    //          chunk completely, and after sending next request to server will receive
                    //          "end of previous response"!!!
                    fgets($connection_resource);

                    break; // last chunk received
                }



                // read one chunk ------------------------------------->>
                $len_step = 8192;


                while( $len ){

                    $len_read = ( $len <= $len_step ) ? $len : $len_step;

                    $response_old_len = strlen($response);

                    $response .= fread($connection_resource, $len_read);

                    $len_read_real = strlen($response) - $response_old_len;

                    $len -= $len_read_real;
                }

                // read separator between chunked blocks (\r\n)
                // WARNING: this operation is very important, otherwise we will not read
                //          chunk completely, and after sending next request to server will receive
                //          "end of previous response"!!!
                fgets($connection_resource);
                // ---------------------------------------------------->>
            }

        } else {
            if( $len === NULL )
                return false;

            $len_step = 8192;

            while( $len ){
                $len_read = ( $len <= $len_step ) ? $len : $len_step;
                $len -= $len_read;
                $response .= fread($connection_resource, $len_read);
            }
        }

        $this->tracingAddInfo('');
        $this->tracingAddInfo('ОТВЕТ: '. "\n\n" . $this->xmlBeautify($response) . "\n");

        return $response;
    }


    /**
     * добавить запись в трассировочные данные
     */
    protected function tracingAddInfo($msg)
    {
        $this->tracing[] = $msg;
    }

    /**
     * добавить запись об ошибке в трассировочные данные
     */
    protected function tracingAddError($msg)
    {
        $this->tracing[] = sprintf('* ОШИБКА: %s', $msg);
    }


    /**
     */
    protected function xmlBeautify($xml)
    {
        $xml = trim($xml);

        require_once __DIR__ . '/xml.php';

        $tokens        = xml_read_tokens($xml);
        $beautiful_xml = xml_beautifier($tokens);

        return $beautiful_xml;
    }


}

