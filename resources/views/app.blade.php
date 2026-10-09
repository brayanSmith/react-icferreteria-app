<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        @php($marca = $page['props']['marca'] ?? null)

        {{-- Si la empresa del usuario tiene ícono, ese es el favicon; si no, el predeterminado --}}
        @if (! empty($marca['icono_url']))
            <link rel="icon" href="{{ $marca['icono_url'] }}">
        @else
            <link rel="icon" href="/favicon.ico" sizes="any">
            <link rel="icon" href="/favicon.svg" type="image/svg+xml">
            <link rel="apple-touch-icon" href="/apple-touch-icon.png">
        @endif

        {{-- Colores de la empresa: se aplican desde el servidor para que no parpadeen al cargar --}}
        @if (! empty($marca['colores']))
            <style>
                :root {
                    @foreach ($marca['colores'] as $variable => $valor)
                        {{ $variable }}: {{ $valor }};
                    @endforeach
                }
            </style>
            <script>
                @foreach (['marca', 'marca-oscuro', 'menu', 'menu-oscuro'] as $atributo)
                    @if (isset($marca['colores']["--{$atributo}"]))
                        document.documentElement.setAttribute('data-{{ $atributo }}', '');
                    @endif
                @endforeach
            </script>
        @endif

        @fonts

        @viteReactRefresh
        @vite(['resources/css/app.css', 'resources/js/app.tsx', "resources/js/pages/{$page['component']}.tsx"])
        <x-inertia::head>
            <title>{{ $marca['nombre'] ?? config('app.name', 'Laravel') }}</title>
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
