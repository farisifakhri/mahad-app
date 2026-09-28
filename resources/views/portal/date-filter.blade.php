<form method="GET" class="sipma-card flex flex-wrap gap-3 items-end">
<label class="text-sm">Dari<input class="mt-1 block w-full rounded-lg border-slate-300" type="date" name="from" value="{{ $range->from }}" required></label>
<label class="text-sm">Sampai<input class="mt-1 block w-full rounded-lg border-slate-300" type="date" name="to" value="{{ $range->to }}" required></label>
<button class="self-end rounded-lg bg-emerald-700 px-4 py-2 text-white" type="submit">Tampilkan</button>
</form>
@if($errors->any())<ul class="mb-3 text-red-700">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>@endif
