@props(['title' => '-', 'value' => '-', 'icon' => 'home'])
<div class="bg-white rounded-2xl shadow p-5">
  <div class="text-center">
    <div class="mx-auto mb-2 h-8 w-8 opacity-60">
      <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor"><path d="M3 9.75 12 3l9 6.75V21a.75.75 0 0 1-.75.75H3.75A.75.75 0 0 1 3 21z"/></svg>
    </div>
    <div class="text-sm font-semibold">{{ $title }}</div>
    <div class="mt-1 text-lg md:text-xl font-bold">Rp.{{ $value }}</div>
  </div>
</div>