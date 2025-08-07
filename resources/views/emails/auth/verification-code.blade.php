<x-mail::message>
# Kode Verifikasi Akun

Gunakan kode di bawah ini untuk memverifikasi alamat email Anda.

<x-mail::panel>
{{ $code }}
</x-mail::panel>

Kode ini akan kedaluwarsa dalam 10 menit. Jika Anda tidak merasa mendaftar, abaikan email ini.

Terima kasih,<br>
{{ config('app.name') }}
</x-mail::message>
