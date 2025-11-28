<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">

  <title>{{ $title ?? 'DCode Mania' }}</title>
  @vite(['resources/css/app.css', 'resources/js/app.js'])
  @livewireStyles
</head>
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('swal', data => {
    Swal.fire({
        title: data.title || 'Notification',
        text: data.message || 'Product Added to Cart Successfully!',
        icon: data.icon || 'success',
        toast: true,
        position: 'bottom-end',
        showConfirmButton: false,
        timer: 2000,
            });
        });
    });
</script>



<body class="bg-slate-200 dark:bg-slate-700">
  @livewire('partials.navbar')
  {{-- <main class="w-full max-w-[85rem] py-10 px-4 sm:px-6 lg:px-8 mx-auto"> --}}
  <main>
    {{ $slot }}
  </main>
  @livewire('partials.footer')
  @livewireScripts

 <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>



</body>

</html>