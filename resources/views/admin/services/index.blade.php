@extends('admin.layout')
@section('title','Layanan & Daftar Harga - Qxpress Laundry')

@section('content')

<div x-data="{ 
  passwordInput: '', 
  correctPassword: '{{ $adminPassword }}',
  isPasswordCorrect() { 
    return this.passwordInput === this.correctPassword; 
  },
  showToast(message, type = 'success') {
    const toast = document.createElement('div');
    toast.className = `fixed top-4 right-4 px-6 py-3 rounded-lg shadow-lg text-white z-50 ${type === 'success' ? 'bg-green-600' : 'bg-red-600'}`;
    toast.textContent = message;
    document.body.appendChild(toast);
    setTimeout(() => toast.remove(), 3000);
  },
  async updateService(event, serviceId) {
    event.preventDefault();
    const form = event.target;
    const formData = new FormData(form);
    
    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });
      
      const data = await response.json();
      
      if (response.ok) {
        this.showToast(data.message || 'Layanan berhasil diperbarui', 'success');
        // Update tanggal terakhir diupdate di UI
        const dateCell = form.closest('tr').querySelector('td:nth-child(4)');
        if (dateCell) {
          const today = new Date();
          const months = ['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des'];
          dateCell.textContent = today.getDate() + ' ' + months[today.getMonth()] + ' ' + today.getFullYear();
        }
      } else {
        this.showToast(data.message || 'Terjadi kesalahan', 'error');
      }
    } catch (error) {
      this.showToast('Gagal memperbarui layanan', 'error');
    }
  },
  async deleteService(event, serviceId) {
    event.preventDefault();
    if (!confirm('Yakin ingin menghapus layanan ini?')) return;
    
    const form = event.target;
    const formData = new FormData(form);
    
    try {
      const response = await fetch(form.action, {
        method: 'POST',
        body: formData,
        headers: {
          'X-Requested-With': 'XMLHttpRequest',
          'Accept': 'application/json'
        }
      });
      
      const data = await response.json();
      
      if (response.ok) {
        this.showToast(data.message || 'Layanan berhasil dihapus', 'success');
        // Hapus baris dari tabel
        form.closest('tr').remove();
      } else {
        this.showToast(data.message || 'Terjadi kesalahan', 'error');
      }
    } catch (error) {
      this.showToast('Gagal menghapus layanan', 'error');
    }
  }
}">

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
  <div class="flex items-center justify-between mb-4">
    <div class="text-xl font-bold">Tambah Layanan</div>
    <div class="flex items-center gap-2">
      <label for="adminPasswordTop" class="text-sm font-medium text-gray-700">Password:</label>
      <input 
        type="password" 
        id="adminPasswordTop"
        x-model="passwordInput"
        placeholder="Masukkan password"
        class="border rounded px-3 py-1.5 text-sm w-48 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        autocomplete="off">
      <span x-show="isPasswordCorrect()" class="text-green-600 text-sm">✓</span>
      <span x-show="!isPasswordCorrect() && passwordInput !== ''" class="text-red-600 text-sm">✗</span>
    </div>
  </div>

  <form method="POST" action="{{ route('admin.services.store') }}" class="grid md:grid-cols-3 gap-4 service-create-form">
    @csrf
    <input name="nama_service" placeholder="Nama Layanan" class="border rounded px-3 py-2" value="{{ old('nama_service') }}" required>

    {{-- Input harga --}}
    <div class="relative">
      <span class="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500 select-none">Rp</span>
      <input name="harga_service" placeholder="Harga"
             type="number" 
             min="0"
             class="border rounded pl-8 pr-2 py-2 w-full"
             value="{{ old('harga_service') }}"
             autocomplete="off"
             required>
    </div>

    <button 
      type="submit" 
      class="px-5 py-2 rounded text-white transition-colors"
      :class="isPasswordCorrect() ? 'bg-blue-600 hover:bg-blue-700 cursor-pointer' : 'bg-gray-400 cursor-not-allowed'"
      :disabled="!isPasswordCorrect()">
      Simpan
    </button>
  </form>
</div>

<div class="mt-8 bg-white p-5 rounded-xl shadow">
  <div class="flex items-center justify-between mb-4">
    <div class="text-xl font-bold">Layanan & Daftar Harga</div>
    <div class="flex items-center gap-2">
      <label for="adminPassword" class="text-sm font-medium text-gray-700">Password:</label>
      <input 
        type="password" 
        id="adminPassword"
        x-model="passwordInput"
        placeholder="Masukkan password"
        class="border rounded px-3 py-1.5 text-sm w-48 focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
        autocomplete="off">
      <span x-show="isPasswordCorrect()" class="text-green-600 text-sm">✓</span>
      <span x-show="!isPasswordCorrect() && passwordInput !== ''" class="text-red-600 text-sm">✗</span>
    </div>
  </div>
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
            <form method="POST" action="{{ route('admin.services.update',$s) }}" class="flex gap-2 items-center" @submit="updateService($event, {{ $s->id }})">
              @csrf @method('PUT')
              <input name="nama_service" value="{{ $s->nama_service }}" class="border rounded px-2 py-1" required>
          </td>

          {{-- Input harga dengan prefix Rp --}}
          <td class="px-3 py-2">
            <div class="relative">
              <span class="absolute left-2 top-1/2 -translate-y-1/2 text-gray-500 select-none">Rp</span>
              <input name="harga_service" type="number" min="0"
                     class="text-right border rounded pl-7 pr-2 py-1 w-32"
                     value="{{ (int)$s->harga_service }}"
                     autocomplete="off"
                     required>
            </div>
          </td>

          <td class="px-3 py-2">{{ $s->updated_at->format('d M Y') }}</td>

          <td class="px-3 py-2 text-right">
              <button 
                type="submit" 
                class="px-3 py-1 text-xs rounded text-white transition-colors"
                :class="isPasswordCorrect() ? 'bg-blue-600 hover:bg-blue-700 cursor-pointer' : 'bg-gray-400 cursor-not-allowed'"
                :disabled="!isPasswordCorrect()">
                update
              </button>
            </form>

            <form method="POST" action="{{ route('admin.services.destroy',$s) }}" class="inline" @submit="deleteService($event, {{ $s->id }})">
              @csrf @method('DELETE')
              <button 
                type="submit" 
                class="px-3 py-1 text-xs rounded text-white transition-colors"
                :class="isPasswordCorrect() ? 'bg-red-600 hover:bg-red-700 cursor-pointer' : 'bg-gray-400 cursor-not-allowed'"
                :disabled="!isPasswordCorrect()">
                hapus
              </button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-4">{{ $services->links('pagination::tailwind') }}</div>
</div>

</div> {{-- End Alpine.js wrapper --}}
@endsection