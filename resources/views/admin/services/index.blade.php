@extends('admin.layout')
@section('title','Layanan & Daftar Harga - Qxpress Laundry')

@section('content')
<div class="bg-white p-5 rounded-xl shadow">
  <div class="text-xl font-bold mb-4">Tambah Layanan</div>
  <form method="POST" action="{{ route('admin.services.store') }}" class="grid md:grid-cols-3 gap-4">
    @csrf
    <input name="nama_service" placeholder="Nama Layanan" class="border rounded px-3 py-2" required>
    <input name="harga_service" placeholder="Harga (Rp)" type="number" class="border rounded px-3 py-2" required>
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
            <form method="POST" action="{{ route('admin.services.update',$s) }}" class="flex gap-2 items-center">
              @csrf @method('PUT')
              <input name="nama_service" value="{{ $s->nama_service }}" class="border rounded px-2 py-1">
          </td>
          <td class="px-3 py-2">
              <input name="harga_service" value="{{ $s->harga_service }}" type="number" class="border rounded px-2 py-1 w-32">
          </td>
          <td class="px-3 py-2">{{ $s->updated_at->format('d M Y') }}</td>
          <td class="px-3 py-2 text-right">
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
@endsection
