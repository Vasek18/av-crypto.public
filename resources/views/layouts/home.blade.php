<!doctype html>
<html lang="{{ app()->getLocale() }}">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible"
          content="IE=edge">
    <meta name="viewport"
          content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    <link href="https://fonts.googleapis.com/css?family=Roboto:300,400,500"
          rel="stylesheet">
    <link rel="stylesheet"
          href="https://maxcdn.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/manifest.json">
    <link rel="mask-icon" href="/safari-pinned-tab.svg" color="#5bbad5">
    <meta name="msapplication-TileColor" content="#00a300">
    <meta name="theme-color" content="#ffffff">
    <style>
        /*todo перенести стили в кссник*/
        html, body {
            background-color: #fff;
            color: #636b6f;
            font-weight: 100;
            margin: 0;
            font-family: 'Raleway', sans-serif;
        }
        .full-screen { /*todo сафари - дерьмо*/
            min-height: 100vh;
        }
        .header {
            position: absolute;
            top: 0px;
            right: 0px;
        }
        .header__links {
            margin-top: 10px;
        }
        .header__links > a {
            color: #636b6f;
            padding: 0 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
        }
        .footer {
            position: relative;
            bottom: 10px;
            left: 0px;
            width: 100%;
            height: 20px;
        }
        .footer a {
            color: #636b6f;
            padding: 10px 25px 0px 25px;
            font-size: 12px;
            font-weight: 600;
            letter-spacing: .1rem;
            text-decoration: none;
            text-transform: uppercase;
            white-space: nowrap;
        }
        .site-creator {
            float: left;
            text-align: center;
        }
        .footer-links {
            float: right;
            text-align: center;
        }
        .home-page {
            align-items: center;
            display: flex;
            justify-content: center;
        }
        .home-page__paragraph {
            font-size: 2em;
            margin: 30px 0px;
            text-align: left;
            font-family: Roboto, Helvetica, Arial, sans-serif;
        }
        .graph-wrapper {
            text-align: center;
        }
        @media screen and (max-width: 767px) {
            .home-page__paragraph {
                font-size: 1.5em;
            }
            .site-creator {
                float: none;
            }
            .footer-links {
                float: none;
            }
        }
    </style>
</head>
<body>
<div class="wrapper" id="app">
    <div class="header clearfix">
        @if (Route::has('login'))
            <div class="pull-right header__links">
                @if (Auth::check())
                    <a href="{{ route('home') }}">Главная</a>
                @else
                    <a href="{{ route('login') }}">{{ __('app.login') }}</a>
                    <a href="{{ route('register') }}">{{ __('app.register') }}</a>
                @endif
            </div>
        @endif
    </div>
    @yield('content')
    <div class="footer clearfix">
        <div class="site-creator">
            <a href="http://aristov-vasiliy.ru/"
               target="_blank">Разработчик проекта <span class="text-primary">Аристов Василий</span></a>
        </div>
        <div class="footer-links">
        </div>
    </div>
</div>
<script src="{{ mix('js/app.js') }}"></script>
</body>
</html>