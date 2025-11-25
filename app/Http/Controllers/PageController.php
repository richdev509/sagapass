<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class PageController extends Controller
{
    // Entreprise
    public function about()
    {
        return view('pages.about');
    }

    public function blog()
    {
        return view('pages.blog');
    }

    public function careers()
    {
        return view('pages.careers');
    }

    public function contact()
    {
        return view('pages.contact');
    }

    // Ressources
    public function documentation()
    {
        return view('pages.documentation');
    }

    public function api()
    {
        return view('pages.api');
    }

    public function support()
    {
        return view('pages.support');
    }

    public function status()
    {
        return view('pages.status');
    }

    // Légal
    public function privacy()
    {
        return view('pages.privacy');
    }

    public function terms()
    {
        return view('pages.terms');
    }

    public function legal()
    {
        return view('pages.legal');
    }

    public function cookies()
    {
        return view('pages.cookies');
    }
}
