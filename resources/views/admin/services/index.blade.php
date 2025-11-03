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

    {{-- Input harga --}}
    <div class="relative">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">Rp</span>
      <input id="harga_create_display" placeholder="Harga"
             type="text" inputmode="numeric"
             class="border rounded pl-8 pr-2 py-2 w-full"
             value="{{ old('harga_service') ? number_format((int)old('harga_service'),0,',','.') : '' }}"
             autocomplete="off"
             oninput="this.nextElementSibling.value=this.value.replace(/\D/g,'');this.value=this.value.replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');">
      <input id="harga_create_value" type="hidden" name="harga_service" value="{{ old('harga_service') }}">
    </div>

    <button class="px-5 py-2 rounded bg-blue-600 text-white hover:brightness-110">Simpan</button>
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
      <tbody class="
      [&_tr:nth-child(odd)]:bg-slate-50/70
      [&_tr:nth-child(even)]:bg-white
      [&_tr:hover]:bg-amber-50/40
    ">
        @foreach($services as $i => $s)
        <tr class="border-t">
          <td class="px-3 py-2">{{ $services->firstItem() + $i }}</td>

          <td class="px-3 py-2">
            <form method="POST" action="{{ route('admin.services.update',$s) }}" class="flex gap-2 items-center">
              @csrf @method('PUT')
              <input name="nama_service" value="{{ $s->nama_service }}" class="border rounded px-2 py-1">
          </td>

          {{-- Input harga dengan prefix Rp --}}
          <td class="px-3 py-2">
            <div class="relative">
              <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-500 select-none">Rp</span>
              <input name="harga_display" type="text" inputmode="numeric"
                     class="text-right border rounded pl-7 pr-2 py-1 w-32"
                     value="{{ number_format((int)$s->harga_service,0,',','.') }}"
                     autocomplete="off"
                     oninput="this.nextElementSibling.value=this.value.replace(/\D/g,'');this.value=this.value.replace(/\D/g,'').replace(/\B(?=(\d{3})+(?!\d))/g,'.');">
              <input type="hidden" name="harga_service" value="{{ (int)$s->harga_service }}">
            </div>
          </td>

          <td class="px-3 py-2">{{ $s->updated_at->format('d M Y') }}</td>

          <td class="px-3 py-2 text-right">
              <button class="px-3 py-1 text-xs rounded bg-blue-600 text-white hover:brightness-110">update</button>
            </form>

            <form method="POST" action="{{ route('admin.services.destroy',$s) }}" class="inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus layanan {{ $s->nama_service }}?');">
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
@endsection