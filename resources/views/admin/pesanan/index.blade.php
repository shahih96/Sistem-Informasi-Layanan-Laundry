@extends('admin.layout')
@section('title','Pesanan Laundry - Qxpress Laundry')

@section('content')
<div class="bg-white p-5 rounded-xl shadow">
  <div class="text-xl font-bold mb-4">Form Input Data Pesanan</div>

  <form method="POST" action="{{ route('admin.pesanan.store') }}" class="grid md:grid-cols-2 gap-4" x-data="pelangganPicker()">
    @csrf
    <!-- Nama Pelanggan -->
    <div class="relative">
      <label class="text-sm">Nama Pelanggan</label>
      <input name="nama_pel"
            x-model="nama"
            @input="onNamaInput()"
            @blur="hideSoon('nama')"
            @focus="showList('nama')"
            autocomplete="off"
            class="mt-1 w-full border rounded px-3 py-2" required>
      <div x-show="openNama && namaSaran.length"
           class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded shadow"
           @mousedown.prevent>
        <template x-for="s in namaSaran" :key="s.key">
          <button type="button"
                  class="block w-full text-left px-3 py-2 hover:bg-gray-50"
                  @click="applySuggestion(s)">
            <div class="font-medium" x-text="s.nama"></div>
            <div class="text-xs text-gray-500" x-text="s.hp"></div>
          </button>
        </template>
      </div>
    </div>

    <!-- Nomor HP -->
    <div class="relative">
      <label class="text-sm">No. HP</label>
      <input name="no_hp_pel"
            x-model="hp"
            @input="onHpInput()"
            @blur="hideSoon('hp')"
            @focus="showList('hp')"
            autocomplete="off"
            class="mt-1 w-full border rounded px-3 py-2" required>
      <div x-show="openHp && hpSaran.length"
           class="absolute z-50 mt-1 w-full bg-white border border-gray-200 rounded shadow"
           @mousedown.prevent>
        <template x-for="s in hpSaran" :key="s.key">
          <button type="button"
                  class="block w-full text-left px-3 py-2 hover:bg-gray-50"
                  @click="applySuggestion(s)">
            <div class="font-medium" x-text="s.hp"></div>
            <div class="text-xs text-gray-500" x-text="s.nama"></div>
          </button>
        </template>
      </div>
    </div>

    <!-- Pilih Layanan -->
    <div>
      <label class="text-sm">Pilih Layanan</label>
      <select name="service_id" class="mt-1 w-full border rounded px-3 py-2" required>
        <option value="">-</option>
        @foreach($services as $s)
          <option value="{{ $s->id }}" @selected(old('service_id')==$s->id)>
            {{ $s->nama_service }} (Rp {{ number_format($s->harga_service,0,',','.') }})
          </option>
        @endforeach
      </select>
    </div>

    <!-- Kuantitas -->
    <div>
      <label class="text-sm">Kuantitas</label>
      <input name="qty" type="number" min="1" value="{{ old('qty',1) }}" class="mt-1 w-full border rounded px-3 py-2" required>
    </div>

    <!-- Metode Pembayaran -->
    <div>
      <label class="text-sm">Metode Pembayaran</label>
      <select name="metode_pembayaran_id" class="mt-1 w-full border rounded px-3 py-2" required>
        @foreach($metodes as $m)
          <option value="{{ $m->id }}" @selected(old('metode_pembayaran_id')==$m->id)>{{ ucfirst($m->nama) }}</option>
        @endforeach
      </select>
    </div>

    <!-- Status Awal -->
    <div>
      <label class="text-sm">Status Pesanan</label>
      <select name="status_awal" class="mt-1 w-full border rounded px-3 py-2" required>
        <option @selected(old('status_awal')==='Diproses')>Diproses</option>
        <option @selected(old('status_awal')==='Selesai')>Selesai</option>
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
          <th class="px-3 py-2 text-center">Qty</th>
          <th class="px-3 py-2 text-right">Total</th>
          <th class="px-3 py-2 text-center">Pembayaran</th>
          <th class="px-3 py-2 text-left">Update Terakhir</th>
          <th class="px-3 py-2"></th>
        </tr>
      </thead>
      <tbody>
      @foreach($pesanan as $p)
        @php
          $qty     = max(1, (int)($p->qty ?? 1));
          $harga   = (int)($p->service->harga_service ?? 0);
          $total   = $qty * $harga;
          $metode  = $p->metode->nama ?? null;
          $isLunas = in_array($metode, ['tunai','qris']);
          $currStatus = optional($p->statuses->first())->keterangan ?? 'Diproses';
        @endphp

        <tr class="border-t {{ $p->is_hidden ? 'bg-red-50/60' : '' }}">
          <td class="px-3 py-2">
            {{ $p->nama_pel }}
            @if($p->is_hidden)
              <span class="ml-2 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] border bg-red-100 text-red-700 border-red-200">Disembunyikan</span>
            @endif
          </td>
          <td class="px-3 py-2">{{ $p->no_hp_pel }}</td>
          <td class="px-3 py-2">{{ $p->service->nama_service ?? '-' }}</td>

          {{-- ✅ Dropdown status versi sederhana --}}
          <td class="px-3 py-2">
            <form method="POST" action="{{ route('admin.status.store') }}">
              @csrf
              <input type="hidden" name="pesanan_id" value="{{ $p->id }}">
              <select name="keterangan" class="border rounded px-2 py-1 text-xs" onchange="this.form.submit()">
                <option value="Diproses" {{ $currStatus==='Diproses' ? 'selected' : '' }}>Diproses</option>
                <option value="Selesai" {{ $currStatus==='Selesai' ? 'selected' : '' }}>Selesai</option>
              </select>
            </form>
          </td>

          <td class="px-3 py-2 text-center">{{ $qty }}</td>
          <td class="px-3 py-2 text-right">Rp {{ number_format($total,0,',','.') }}</td>
          <td class="px-3 py-2 text-center">
            <span class="inline-flex items-center px-2 py-0.5 rounded text-xs
              {{ $isLunas ? 'bg-green-50 text-green-700 border border-green-200' : 'bg-red-50 text-red-700 border border-red-200' }}">
              {{ $isLunas ? 'Lunas' : 'Belum Lunas' }}
            </span>
          </td>
          <td class="px-3 py-2">{{ optional($p->statuses->first())->created_at?->format('d/m/y H:i') }}</td>
          <td class="px-3 py-2 text-right">
            @if(!$p->is_hidden)
              <form method="POST" action="{{ route('admin.pesanan.destroy',$p) }}">
                @csrf @method('DELETE')
                <button class="px-3 py-1 text-xs rounded bg-red-600 text-white">Sembunyikan</button>
              </form>
            @else
              <span class="text-[11px] text-gray-500 italic">Tersembunyi</span>
            @endif
          </td>
        </tr>
      @endforeach
      </tbody>
    </table>
  </div>
  <div class="mt-4">{{ $pesanan->links() }}</div>
</div>

<script>
  // kode pelangganPicker tetap sama
  const CUSTOMERS = [
    @foreach($pelangganOptions as $pl)
      { nama: {!! json_encode($pl->nama_pel) !!}, hp: {!! json_encode($pl->no_hp_pel) !!} },
    @endforeach
  ];
  const norm = s => (s||'').toString().trim().toLowerCase();
  const onlyDigits = s => (s||'').replace(/\D/g,'');
  function lev(a,b){a=norm(a);b=norm(b);const m=a.length,n=b.length;
    if(!m)return n;if(!n)return m;
    const dp=Array.from({length:m+1},(_,i)=>Array(n+1).fill(0));
    for(let i=0;i<=m;i++)dp[i][0]=i;for(let j=0;j<=n;j++)dp[0][j]=j;
    for(let i=1;i<=m;i++){for(let j=1;j<=n;j++){
      const cost=a[i-1]===b[j-1]?0:1;
      dp[i][j]=Math.min(dp[i-1][j]+1,dp[i][j-1]+1,dp[i-1][j-1]+cost);
    }}return dp[m][n];}
  function rankByName(q){q=norm(q);if(!q)return[];return CUSTOMERS.map((c,i)=>{
    const n=norm(c.nama);const s=(n.startsWith(q)?0:n.includes(q)?1:lev(n,q));
    return{key:i,...c,score:s};}).filter(x=>x.score<=3||x.score<=1)
    .sort((a,b)=>a.score-b.score).slice(0,6);}
  function rankByHp(q){q=onlyDigits(q);if(!q)return[];return CUSTOMERS.map((c,i)=>{
    const h=onlyDigits(c.hp);const s=(h.startsWith(q)?0:h.includes(q)?1:lev(h,q));
    return{key:i,...c,score:s};}).filter(x=>x.score<=3||x.score<=1)
    .sort((a,b)=>a.score-b.score).slice(0,6);}
  function pelangganPicker(){return{
    nama:@json(old('nama_pel','')),hp:@json(old('no_hp_pel','')),
    openNama:false,openHp:false,namaSaran:[],hpSaran:[],hideTimer:null,
    showList(w){if(w==='nama')this.openNama=true;else this.openHp=true;},
    hideSoon(w){clearTimeout(this.hideTimer);
      this.hideTimer=setTimeout(()=>{if(w==='nama')this.openNama=false;else this.openHp=false;},120);},
    onNamaInput(){this.namaSaran=rankByName(this.nama);
      this.openNama=!!this.namaSaran.length;
      const e=CUSTOMERS.find(c=>norm(c.nama)===norm(this.nama));if(e&&!this.hp)this.hp=e.hp;},
    onHpInput(){this.hpSaran=rankByHp(this.hp);
      this.openHp=!!this.hpSaran.length;
      const e=CUSTOMERS.find(c=>onlyDigits(c.hp)===onlyDigits(this.hp));if(e&&!this.nama)this.nama=e.nama;},
    applySuggestion(s){this.nama=s.nama;this.hp=s.hp;this.openNama=false;this.openHp=false;}
  }}
</script>
@endsection