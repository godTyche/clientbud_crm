<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ trans('installer_messages.title') }}</title>
    <link rel="icon" type="image/png" href="{{ asset('favicon.png') }}" sizes="16x16"/>

    <link href="{{ asset('installer/css/style.min.css') }}" rel="stylesheet"/>
    @yield('style')


</head>
<body>
<div class="master">
    <div class="box">
        <div class="header">
           <img src="{{ asset('img/worksuite-logo.png') }}" height="40px" alt="">
            <h1 class="header__title">@yield('title')</h1>
        </div>
        <ul class="step">
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::final') }}"><i class="step__icon fa fa-check"></i></li>
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::permissions') }}"><i class="step__icon fa fa-key"></i></li>
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::requirements') }}"><i class="step__icon fa fa-gear"></i></li>
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::environment') }}"><i class="step__icon fa fa-database"></i></li>
            <li class="step__divider"></li>
            <li class="step__item {{ isActive('LaravelInstaller::welcome') }}"><i class="step__icon fa fa-home"></i></li>
            <li class="step__divider"></li>
        </ul>
        <div class="main">
            @yield('container')
        </div>
    </div>
</div>
</body>
@yield('scripts')
</html>
