@if ($errors->any())<div class="sipma-errors" role="alert"><strong>Periksa kembali isian berikut.</strong><ul>@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
<div wire:loading.delay class="sipma-notice" role="status">Memproses perubahan…</div>
