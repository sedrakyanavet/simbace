<?php

namespace App\Http\Controllers;

use App\Components\simBaseAuth;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Laravel\Lumen\Application;

class DataController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * @return View|Application
     */
    public function index()
    {
        return view('app');
    }

    /**
     * @return array
     */
    public function getData(): array
    {
        return (new simBaseAuth())->callFunction('f_api_return_accommodations_data');
    }

    /**
     * @param Request $request
     * @return array
     * @throws ValidationException
     */
    public function saveData(Request $request): array
    {
        $this->validate($request, [
            'data' => 'required|array',
        ]);

        return (new simBaseAuth())->callFunction('f_api_save_accommodations_data', $request->data);
    }
}
