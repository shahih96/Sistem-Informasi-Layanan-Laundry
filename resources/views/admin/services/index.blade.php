@extends('admin.layout')
@section('title','Layanan & Daftar Harga - Qxpress Laundry')

@section('content')

{{-- Flash messages --}}
@if(session('success'))
  <div class="mb-4 p-3 rounded bg-green-50 text-green-700 border border-green-200">{{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">{{ session('error') }}</div>
@endif
@if ($errors->any())
  <div class="mb-4 p-3 rounded bg-red-50 text-red-700 border border-red-200">
    <ul class="list-disc pl-5">
      @foreach ($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<div class="bg-white p-5 rounded-xl shadow">
  <div class="text-xl font-bold mb-4">Tambah Layanan</div>

  <form method="POST" action="{{ route('admin.services.store') }}" class="grid md:grid-cols-3 gap-4 service-create-form">
    @csrf
    <input name="nama_service" placeholder="Nama Layanan" class="border rounded px-3 py-2" value="{{ old('nama_service') }}" required>

    {{-- Input harga tampilan (diformat), dan hidden aslinya --}}
    <div>
      <input id="harga_create_display" placeholder="Harga (Rp)"
             type="text" inputmode="numeric"
             class="border rounded px-3 py-2 money-input w-full"
             value="{{ old('harga_service') ? number_format((int)old('harga_service'),0,',','.') : '' }}"
             autocomplete="off">
      <input id="harga_create_value" type="hidden" name="harga_service" value="{{ old('harga_service') }}">
    </div>

    <button class="px-5 py-2 rounded bg-gray-800 text-white">Simpan</button>
  </form>
</div>

<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="text-xl font-bold mb-4">Layanan & Daftar Harga</div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">No</th>
          <th class="px-3 py-2 text-left">Nama Layanan</th>
          <th class="px-3 py-2 text-left">Harga</th>
          <th class="px-3 py-2 text-left">Terakhir Diupdate</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        @foreach($services as $i => $s)
        <tr class="border-t">
          <td class="px-3 py-2">{{ $services->firstItem() + $i }}</td>

          <td class="px-3 py-2">
            <form method="POST" action="{{ route('admin.services.update',$s) }}"
                  class="flex gap-2 items-center service-update-form">
              @csrf @method('PUT')
              <input name="nama_service" value="{{ $s->nama_service }}" class="border rounded px-2 py-1">
          </td>

          <td class="px-3 py-2">
              {{-- Tampilkan yang terformat di input, kirim nilai asli via hidden --}}
              <input type="text" inputmode="numeric"
                     class="border rounded px-2 py-1 w-36 money-input"
                     value="{{ number_format((int)$s->harga_service,0,',','.') }}"
                     autocomplete="off">
              <input type="hidden" name="harga_service" value="{{ (int)$s->harga_service }}">
          </td>

          <td class="px-3 py-2">{{ $s->updated_at->format('d M Y') }}</td>

          <td class="px-3 py-2 text-right">
              <span class="mr-3 font-medium text-gray-800">
                {{-- Di tampilan tabel biasa (di luar input), bisa juga: Rp {{ number_format($s->harga_service,0,',','.') }} --}}
              </span>
              <button class="px-3 py-1 text-xs rounded bg-gray-800 text-white">update</button>
            </form>

            <form method="POST" action="{{ route('admin.services.destroy',$s) }}" class="inline">
              @csrf @method('DELETE')
              <button class="px-3 py-1 text-xs rounded bg-red-600 text-white">hapus</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-4">{{ $services->links('pagination::tailwind') }}</div>
</div>

{{-- Formatter rupiah sederhana --}}
<script>
  // format angka -> "1.234.567"
  function formatIDR(n) {
    if (!n) return '';
    return n.replace(/\D/g,'').replace(/^0+/, '').replace(/\B(?=(\d{3})+(?!\d))/g, '.');
  }
  // "1.234.567" -> 1234567
  function parseIDR(n) {
    return n.replace(/\D/g,'');
  }

  // CREATE
  (function(){
    const disp = document.getElementById('harga_create_display');
    const val  = document.getElementById('harga_create_value');
    if (disp && val) {
      disp.addEventListener('input', () => {
        disp.value = formatIDR(disp.value);
        val.value  = parseIDR(disp.value);
      });
      // init
      disp.value = formatIDR(disp.value);
      val.value  = parseIDR(disp.value);
    }
  })();

  // UPDATE: setiap baris punya money-input + hidden setelahnya
  document.querySelectorAll('form.service-update-form').forEach((frm) => {
    const moneyInput = frm.querySelector('input.money-input');
    const hidden     = frm.querySelector('input[type="hidden"][name="harga_service"]');

    if (moneyInput && hidden) {
      // init view
      moneyInput.value = formatIDR(moneyInput.value);

      moneyInput.addEventListener('input', () => {
        moneyInput.value = formatIDR(moneyInput.value);
        hidden.value     = parseIDR(moneyInput.value);
      });

      frm.addEventListener('submit', () => {
        hidden.value     = parseIDR(moneyInput.value);
      });
    }
  });

  // CREATE form ensure hidden numeric on submit
  const createForm = document.querySelector('form.service-create-form');
  if (createForm) {
    createForm.addEventListener('submit', () => {
      const disp = document.getElementById('harga_create_display');
      const val  = document.getElementById('harga_create_value');
      if (disp && val) val.value = parseIDR(disp.value);
    });
  }
</script>
@endsection
