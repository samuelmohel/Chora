<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'plan.paytm',
        'iyzipay/callback/*',
        'invoice/iyzipay/callback/*',
        'retainer/iyzipay/callback/*',
        'paytab-success/*',
        'retainer-paytab-success/*',
        '/aamarpay*'
        
    ];
}
