@extends('admin.layout')
@section('title','Pesanan Laundry - Qxpress Laundry')

@section('content')
<div class="bg-white p-5 rounded-xl shadow">
  <div class="text-xl font-bold mb-4">Form Input Data Pesanan</div>

  <form method="POST" action="{{ route('admin.pesanan.store') }}" class="grid md:grid-cols-2 gap-4">
    @csrf
    <div>
      <label class="text-sm">Nama Pelanggan</label>
      <input name="nama_pel" class="mt-1 w-full border rounded px-3 py-2" required>
    </div>
    <div>
      <label class="text-sm">No. HP</label>
      <input name="no_hp_pel" class="mt-1 w-full border rounded px-3 py-2" required>
    </div>
    <div>
      <label class="text-sm">Pilih Layanan</label>
      <select name="service_id" class="mt-1 w-full border rounded px-3 py-2">
        <option value="">-</option>
        @foreach($services as $s)
          <option value="{{ $s->id }}">{{ $s->nama_service }} (Rp {{ number_format($s->harga_service,0,',','.') }})</option>
        @endforeach
      </select>
    </div>
    <div>
      <label class="text-sm">Status Pesanan</label>
      <select name="status_awal" class="mt-1 w-full border rounded px-3 py-2" required>
        <option>Diterima</option>
        <option>Diproses</option>
        <option>Antri</option>
        <option>Selesai</option>
        <option>Diambil</option>
      </select>
    </div>
    <div class="md:col-span-2">
      <button class="px-5 py-3 rounded-lg bg-gray-800 text-white">Simpan Pesanan</button>
    </div>
  </form>
</div>

<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="text-xl font-bold mb-4">Tabel Pesanan Laundry</div>
  <div class="overflow-x-auto">
    <table class="min-w-full text-sm">
      <thead class="bg-gray-50">
        <tr>
          <th class="px-3 py-2 text-left">Pelanggan</th>
          <th class="px-3 py-2 text-left">No HP</th>
          <th class="px-3 py-2 text-left">Layanan</th>
          <th class="px-3 py-2 text-left">Status</th>
          <th class="px-3 py-2 text-left">Update Terakhir</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
        @foreach($pesanan as $p)
          <tr class="border-t">
            <td class="px-3 py-2">{{ $p->nama_pel }}</td>
            <td class="px-3 py-2">{{ $p->no_hp_pel }}</td>
            <td class="px-3 py-2">{{ $p->service->nama_service ?? '-' }}</td>
            <td class="px-3 py-2">
              <form method="POST" action="{{ route('admin.status.store') }}" class="flex items-center gap-2">
                @csrf
                <input type="hidden" name="pesanan_id" value="{{ $p->id }}">
                <select name="keterangan" class="border rounded px-2 py-1 text-xs">
                  @php $ops = ['Diterima','Antri','Diproses','Selesai','Diambil']; @endphp
                  @foreach($ops as $op)
                    <option {{ (optional($p->statuses->first())->keterangan === $op)?'selected':'' }}>{{ $op }}</option>
                  @endforeach
                </select>
                <button class="text-xs px-2 py-1 rounded bg-gray-800 text-white">Ubah</button>
              </form>
            </td>
            <td class="px-3 py-2">{{ optional($p->statuses->first())->created_at?->format('d/m/y H:i') }}</td>
            <td class="px-3 py-2 text-right">
              <form method="POST" action="{{ route('admin.pesanan.destroy',$p) }}">
                @csrf @method('DELETE')
                <button class="text-xs underline text-red-600">Hapus</button>
              </form>
            </td>
          </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-4">{{ $pesanan->links() }}</div>
</div>
@endsection
