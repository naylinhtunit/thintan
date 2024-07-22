<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Config;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use SiteHelpers;

class ConfigController extends Controller
{
    public function saveConfig(Request $request)
    {
        $files = $request->file();

        $input = $request->all();
        $code = $request->input('code');

        unset($input['_token'], $input['code']);

        foreach ($files as $file_key => $file_array) {
            if (Storage::exists($input['old_' . $file_key])) {
                Storage::delete($input['old_' . $file_key]);
            }
            unset($input['old_' . $file_key]);

            $file_name = $request->file($file_key)->getClientOriginalName();
            $path = "config";
            $new_file_name = SiteHelpers::checkFileName($path, $file_name);
            $path = $request->file($file_key)->storeAs($path, $new_file_name);

            $input[$file_key] = $path;
        }

        Config::save_options($code, $input);
        return redirect()->back()->with('success', 'Configuration saved successfully.');
    }

    public function pageHome(Request $request)
    {
        $config = Config::get_options('pageHome');
        return view('admin.config.page_home', compact('config'));
    }

    public function pageAbout(Request $request)
    {
        $config = Config::get_options('pageAbout');
        return view('admin.config.page_about', compact('config'));
    }

    public function pageContact(Request $request)
    {
        $config = Config::get_options('pageContact');
        return view('admin.config.page_contact', compact('config'));
    }

    public function settingGeneral(Request $request)
    {
        $config = Config::get_options('settingGeneral');
        return view('admin.config.setting_general', compact('config'));
    }

    public function settingEmail(Request $request)
    {
        $config = Config::get_options('settingEmail');
        return view('admin.config.setting_email', compact('config'));
    }
}
