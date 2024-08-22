<?php

namespace App\Http\Controllers;

use App\Models\Branch;

abstract class Controller
{
    protected $branch;

    public function __construct()
    {

        if (auth()->user()->fk_branch == 0) {
            $branch = Branch::all();
        } else {
            $branch = Branch::where('id', auth()->user()->fk_branch)->get();
        }
        $this->branch = $branch;
    }
}
