// JavaScript dasar: menu mobile, konfirmasi hapus, flash otomatis, kalkulator biaya, cek jadwal
(function () {
  var burger = document.querySelector('.burger'), nav = document.querySelector('.nav nav');
  if (burger) burger.addEventListener('click', function () {
    var o = nav.classList.toggle('open'); burger.setAttribute('aria-expanded', o);
  });
  document.querySelectorAll('[data-confirm]').forEach(function (el) {
    el.addEventListener('submit', function (ev) { if (!confirm(el.getAttribute('data-confirm'))) ev.preventDefault(); });
  });
  document.querySelectorAll('.alert[data-auto]').forEach(function (a) { setTimeout(function () { a.style.display = 'none'; }, 6000); });

  // Form sewa: hitung biaya otomatis + tampilkan jadwal terisi
  var f = document.getElementById('form-sewa');
  if (!f) return;
  var unit = f.querySelector('[name=unit]'), tgl = f.querySelector('[name=tanggal]'),
      jam = f.querySelector('[name=jam]'), dur = f.querySelector('[name=durasi]');
  var rp = function (n) { return 'Rp ' + n.toLocaleString('id-ID'); };
  function hitung() {
    var opt = unit.options[unit.selectedIndex], harga = parseInt(opt.getAttribute('data-harga') || 0, 10),
        d = parseInt(dur.value || 0, 10), j = parseInt(jam.value || 0, 10);
    document.getElementById('s-unit').textContent = harga ? opt.getAttribute('data-nama') : '-';
    document.getElementById('s-harga').textContent = harga ? rp(harga) + '/jam' : '-';
    document.getElementById('s-durasi').textContent = d ? d + ' jam' : '-';
    document.getElementById('s-waktu').textContent = (j && d) ? pad(j) + ':00 - ' + pad((j + d) % 24) + ':00' : '-';
    document.getElementById('s-total').textContent = rp(harga * d);
  }
  function pad(n) { return (n < 10 ? '0' : '') + n; }
  function jadwal() {
    var box = document.getElementById('jadwal');
    if (!unit.value || !tgl.value) { box.innerHTML = ''; return; }
    fetch(f.getAttribute('data-api') + '?unit=' + encodeURIComponent(unit.value) + '&tanggal=' + encodeURIComponent(tgl.value))
      .then(function (r) { return r.json(); })
      .then(function (rows) {
        if (!rows.length) { box.innerHTML = '<li class="kosong">Belum ada jadwal terisi, semua jam kosong.</li>'; return; }
        box.innerHTML = rows.map(function (r) { return '<li>Terisi ' + r.mulai + ' - ' + r.selesai + '</li>'; }).join('');
      }).catch(function () { box.innerHTML = ''; });
  }
  [unit, tgl, jam, dur].forEach(function (el) { el.addEventListener('change', hitung); el.addEventListener('input', hitung); });
  [unit, tgl].forEach(function (el) { el.addEventListener('change', jadwal); });
  hitung(); jadwal();
})();
