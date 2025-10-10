<?php
if (session_status() === PHP_SESSION_NONE) {
  session_start();
}
date_default_timezone_set('Asia/Jakarta'); // WIB
?>

<style>
  .btn-panduan {
    background-color: #17a2b8 !important;
    color: #fff !important;
    border-radius: 50px;
    padding: 5px 15px;
    font-size: 14px;
    font-weight: 500;
    border: none;
  }

  .btn-panduan:hover,
  .btn-panduan:focus,
  .btn-panduan:active,
  .btn-panduan.active {
    background-color: #138496 !important;
    color: #fff !important;
    box-shadow: none !important;
  }



  .modal-full-width {
    max-width: 98% !important; /* hampir full screen */
    margin: 1rem auto;
  }

  @media (min-width: 1200px) {
    .modal-full-width .modal-content {
      padding: 1rem 2rem;
    }
  
  .modal-body {
  max-height: 80vh;
  overflow-y: auto;
}

.modal-full-width {
  max-width: 50% !important;
  margin: 1rem auto;
}

.modal-body {
  max-height: 60vh;
  overflow-y: auto;
}

.chat-bubble-left {
  background-color: #f1f1f1;
  color: #000;
  padding: 8px 12px;
  border-radius: 15px 15px 15px 0;
  margin-bottom: 5px;
  display: inline-block;
  max-width: 80%;
}

.chat-bubble-right {
  background-color: #6777ef;
  color: #fff;
  padding: 8px 12px;
  border-radius: 15px 15px 0 15px;
  margin-bottom: 5px;
  display: inline-block;
  max-width: 80%;
}


.chat-message {
  padding: 5px;
  margin-bottom: 5px;
  border-radius: 5px;
}

.chat-message.received {
  background-color: #f1f1f1;
  text-align: left;
}

.chat-message.sent {
  background-color: #d1ecf1;
  text-align: right;
}



</style>

<div class="navbar-bg"></div>
<nav class="navbar navbar-expand-lg main-navbar">
  <form class="form-inline mr-auto">
    <ul class="navbar-nav mr-3">
      <li>
        <a href="#" data-toggle="sidebar" class="nav-link nav-link-lg">
          <i class="fas fa-bars"></i>
        </a>
      </li>
    </ul>

    <!-- Jam Digital -->
    <div class="text-light font-weight-bold ml-3" id="jam-digital" style="font-size: 16px;"></div>

    <!-- Tombol Panduan Tiket -->
    <button type="button" class="btn btn-panduan ml-3" data-toggle="modal" data-target="#panduanModal">
      <i class="fas fa-info-circle mr-1"></i> Panduan Tiket
    </button>


      <button type="button" class="btn btn-flat text-white ml-2" style="background: transparent; border: none;" 
              data-toggle="modal" data-target="#catatanModal" data-toggle="tooltip" title="Catatan Kerja">
        <i class="fas fa-pen-square" style="font-size: 20px;"></i>
      </button>


<!-- Tombol Pesan (ikon saja, tidak bulat, tidak hover mencolok) -->
<button type="button" class="btn btn-flat text-white ml-2" style="background: transparent; border: none;" data-toggle="modal" data-target="#pesanModal" data-toggle="tooltip" title="Kirim Pesan">
  <i class="fas fa-envelope" style="font-size: 20px;"></i>
</button>
<button type="button" class="btn btn-flat text-white ml-2" style="background: transparent; border: none;" data-toggle="modal" data-target="#fungsiMenuModal" data-toggle="tooltip" title="Fungsi Menu">
  <i class="fas fa-question-circle" style="font-size: 20px;"></i>
</button>





  </form>

  <ul class="navbar-nav navbar-right">
    <li class="nav-item d-flex align-items-center mr-3">
      <i class="fas fa-user-circle text-white mr-2" style="font-size: 20px;"></i>
      <span class="text-white font-weight-bold" style="font-size: 15px;">
        <?= isset($_SESSION['nama']) ? htmlspecialchars($_SESSION['nama']) : 'Pengguna' ?>
      </span>
    </li>

    <li class="nav-item">
      <a href="logout.php" class="btn btn-danger btn-sm font-weight-bold" style="display: flex; align-items: center;">
        <i class="fas fa-sign-out-alt mr-1 text-white"></i> 
        <span class="text-white">Keluar</span>
      </a>
    </li>
  </ul>
</nav>


<!-- Modal Chat Pesan -->
<div class="modal fade" id="pesanModal" tabindex="-1" role="dialog" aria-labelledby="pesanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 800px;">
    <div class="modal-content">
      <div class="modal-header bg-primary text-white">
        <h5 class="modal-title"><i class="fas fa-comments"></i> Chat Pesan</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <div class="modal-body p-0">
        <div class="row no-gutters">
          <!-- Sidebar User List -->
          <div class="col-md-4 border-right" style="max-height: 400px; overflow-y: auto;" id="daftar-pengguna">
            <!-- Dinamis via JavaScript -->
            <div class="text-center text-muted p-3"><em>Memuat pengguna...</em></div>
          </div>

          <!-- Chat Area -->
          <div class="col-md-8 d-flex flex-column" style="height: 400px;">
            <div class="p-2 bg-light border-bottom" id="chat-header" style="font-weight: bold;">
              Pilih pengguna untuk mulai chat
            </div>
            <div class="flex-grow-1 p-2" id="chat-body" style="overflow-y: auto; background: #f9f9f9;"></div>
            <div class="p-2 border-top">
              <form id="formChat" method="POST" class="d-flex" style="display: none;">
                <input type="hidden" name="penerima_id" id="penerima_id">
                <input type="text" name="pesan" class="form-control mr-2" placeholder="Tulis pesan..." required>
                <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i></button>
              </form>
            </div>
          </div>
        </div>
      </div>

    </div>
  </div>
</div>




<!-- Modal Panduan Tiket -->
<div class="modal fade" id="panduanModal" tabindex="-1" role="dialog" aria-labelledby="panduanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-xl modal-dialog-centered modal-full-width" role="document">

    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="panduanModalLabel"><i class="fas fa-info-circle"></i> Kategori Layanan IT</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>


     <div class="modal-body">
  <div class="row">
    <!-- Kolom IT Software -->
    <div class="col-md-6">
      <h6 class="text-info font-weight-bold mb-3">
        <i class="fas fa-laptop-code"></i> IT Software
      </h6>
      <?php
      include 'koneksi.php';
      $querySoftware = "SELECT nama_kategori FROM kategori_software ORDER BY nama_kategori ASC";
      $resultSoftware = mysqli_query($conn, $querySoftware);

      if (mysqli_num_rows($resultSoftware) > 0) {
        while ($row = mysqli_fetch_assoc($resultSoftware)) {
          echo '
          <div class="mb-2 p-2 rounded text-white" style="background-color: #007bff;">
            <i class="fas fa-check-circle mr-2"></i> ' . htmlspecialchars($row['nama_kategori']) . '
          </div>';
        }
      } else {
        echo '<div class="text-muted"><em>Data tidak tersedia</em></div>';
      }
      ?>
    </div>

    <!-- Kolom IT Hardware -->
    <div class="col-md-6">
      <h6 class="text-dark font-weight-bold mb-3">
        <i class="fas fa-desktop"></i> IT Hardware
      </h6>
      <?php
      $queryHardware = "SELECT nama_kategori FROM kategori_hardware ORDER BY nama_kategori ASC";
      $resultHardware = mysqli_query($conn, $queryHardware);

      if (mysqli_num_rows($resultHardware) > 0) {
        while ($row = mysqli_fetch_assoc($resultHardware)) {
          echo '
          <div class="mb-2 p-2 rounded text-white" style="background-color: #343a40;">
            <i class="fas fa-check-circle mr-2"></i> ' . htmlspecialchars($row['nama_kategori']) . '
          </div>';
        }
      } else {
        echo '<div class="text-muted"><em>Data tidak tersedia</em></div>';
      }
      ?>
    </div>
  </div>
</div>


      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>


<!-- Modal Fungsi Menu -->
<div class="modal fade" id="fungsiMenuModal" tabindex="-1" role="dialog" aria-labelledby="fungsiMenuModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
    <div class="modal-content">
      <div class="modal-header bg-success text-white">
        <h5 class="modal-title" id="fungsiMenuModalLabel"><i class="fas fa-question-circle"></i> Daftar Fungsi Menu</h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">

       <ul class="list-group list-group-flush">
  <li class="list-group-item"><i class="fas fa-fire mr-2"></i><strong>Dashboard:</strong> Ringkasan informasi penting dan status tiket</li>
  <li class="list-group-item"><i class="fas fa-user-tie mr-2"></i><strong>Dashboard Direktur:</strong> Ringkasan Direktur</li>

  <li class="list-group-item"><i class="fas fa-cogs mr-2"></i><strong>Data Tiket IT Hard:</strong> Monitoring hardware IT</li>
  <li class="list-group-item"><i class="fas fa-code mr-2"></i><strong>Data Tiket IT Soft:</strong> Monitoring software IT</li>
  <li class="list-group-item"><i class="fas fa-calendar-times mr-2"></i><strong>Data Off-Duty:</strong> Data cuti/tidak bertugas</li>
  <li class="list-group-item"><i class="fas fa-user-slash mr-2"></i><strong>Off-Duty:</strong> Pengajuan cuti/tidak bertugas</li>
  <li class="list-group-item"><i class="fas fa-ticket-alt mr-2"></i><strong>Tiket IT Hard:</strong> Order tiket hardware IT</li>
  <li class="list-group-item"><i class="fas fa-ticket-alt mr-2"></i><strong>Tiket IT Soft:</strong> Order tiket software IT</li>
  <li class="list-group-item"><i class="fas fa-stopwatch mr-2"></i><strong>Handling Time:</strong> Waktu penanganan tiket</li>
  <li class="list-group-item"><i class="fas fa-file-alt mr-2"></i><strong>SPO IT:</strong> Standar Operasional Prosedur IT</li>
  <li class="list-group-item"><i class="fas fa-file-signature mr-2"></i><strong>Input SPO IT:</strong> Input dokumen SPO IT</li>
  <li class="list-group-item"><i class="fas fa-scroll mr-2"></i><strong>Berita Acara:</strong> Catatan pemeriksaan barang IT</li>
  <li class="list-group-item"><i class="fas fa-boxes mr-2"></i><strong>Data Barang IT:</strong> Inventaris perangkat IT</li>
  <li class="list-group-item"><i class="fas fa-sync-alt mr-2"></i><strong>Maintenance Rutin:</strong> Perawatan rutin perangkat IT</li>
  <li class="list-group-item"><i class="fas fa-link mr-2"></i><strong>Koneksi Bridging:</strong> Koneksi sistem integrasi</li>

  <li class="list-group-item"><i class="fas fa-ticket-alt mr-2"></i><strong>Order Tiket Sarpras:</strong> Permintaan sarana prasarana</li>
  <li class="list-group-item"><i class="fas fa-clipboard-list mr-2"></i><strong>Data Tiket Sarpras:</strong> Daftar tiket sarpras</li>
  <li class="list-group-item"><i class="fas fa-stopwatch mr-2"></i><strong>Handling Time Sarpras:</strong> Waktu penanganan sarpras</li>
  <li class="list-group-item"><i class="fas fa-boxes mr-2"></i><strong>Barang Sarpras:</strong> Inventaris sarana prasarana</li>
  <li class="list-group-item"><i class="fas fa-cogs mr-2"></i><strong>Maintenance Sarpras:</strong> Perawatan rutin sarpras</li>

  <li class="list-group-item"><i class="fas fa-chart-line mr-2"></i><strong>Indikator Mutu:</strong> Monitoring mutu dan kinerja</li>

  <li class="list-group-item"><i class="fas fa-file-alt mr-2"></i><strong>Data Dokumen:</strong> Dokumen akreditasi</li>
  <li class="list-group-item"><i class="fas fa-plus mr-2"></i><strong>Input Dokumen:</strong> Upload dokumen akreditasi</li>
  <li class="list-group-item"><i class="fas fa-database mr-2"></i><strong>Master Pokja:</strong> Data tim kerja akreditasi</li>

  <li class="list-group-item"><i class="fas fa-user-clock mr-2"></i><strong>Transaksi Gaji:</strong> Input dan pengelolaan gaji</li>
  <li class="list-group-item"><i class="fas fa-user-clock mr-2"></i><strong>Data Gaji:</strong> Rekapitulasi gaji pegawai</li>

  <li class="list-group-item"><i class="fas fa-envelope-open-text mr-2"></i><strong>Surat Masuk:</strong> Kelola surat masuk</li>
  <li class="list-group-item"><i class="fas fa-paper-plane mr-2"></i><strong>Surat Keluar:</strong> Kelola surat keluar</li>
  <li class="list-group-item"><i class="fas fa-archive mr-2"></i><strong>Arsip Digital:</strong> Dokumentasi arsip digital</li>
  <li class="list-group-item"><i class="fas fa-calendar-alt mr-2"></i><strong>Agenda Direktur:</strong> Jadwal penting pimpinan</li>
  <li class="list-group-item"><i class="fas fa-calendar-check mr-2"></i><strong>Lihat Agenda:</strong> Monitoring agenda</li>
  <li class="list-group-item"><i class="fas fa-folder-open mr-2"></i><strong>Kategori Arsip:</strong> Pengelompokan arsip</li>

  <li class="list-group-item"><i class="fas fa-clipboard-list mr-2"></i><strong>Catatan Kerja:</strong> Rekap aktivitas kerja</li>
  <li class="list-group-item"><i class="fas fa-calendar-check mr-2"></i><strong>Laporan Harian:</strong> Catatan harian karyawan</li>
  <li class="list-group-item"><i class="fas fa-file-alt mr-2"></i><strong>Laporan Bulanan:</strong> Rekap bulanan</li>
  <li class="list-group-item"><i class="fas fa-calendar-alt mr-2"></i><strong>Laporan Tahunan:</strong> Rekap tahunan</li>

  <li class="list-group-item"><i class="fas fa-users mr-2"></i><strong>Data Karyawan:</strong> Data pegawai</li>
  <li class="list-group-item"><i class="fas fa-users mr-2"></i><strong>Exit Clearance:</strong> Proses keluar pegawai</li>
  <li class="list-group-item"><i class="fas fa-calendar-alt mr-2"></i><strong>Master Cuti:</strong> Data cuti pegawai</li>
  <li class="list-group-item"><i class="fas fa-file-signature mr-2"></i><strong>Pengajuan Cuti:</strong> Form pengajuan cuti</li>
  <li class="list-group-item"><i class="fas fa-calendar-check mr-2"></i><strong>Jatah Cuti:</strong> Kuota cuti pegawai</li>

  <li class="list-group-item"><i class="fas fa-door-open mr-2"></i><strong>Izin Keluar:</strong> Form izin keluar kantor</li>

  <li class="list-group-item"><i class="fas fa-clock mr-2"></i><strong>Jadwal:</strong> Jadwal dinas dan absensi</li>

  <li class="list-group-item"><i class="fas fa-users-cog mr-2"></i><strong>Rekruitment:</strong> Proses penerimaan pegawai</li>

  <li class="list-group-item"><i class="fas fa-globe mr-2"></i><strong>Website:</strong> Konten website dan dokumentasi</li>

  <li class="list-group-item"><i class="fas fa-folder mr-2"></i><strong>Master Data:</strong> Data perusahaan dan pengguna</li>

  <li class="list-group-item"><i class="fas fa-cogs mr-2"></i><strong>Setting:</strong> Pengaturan akun dan profil</li>

  <li class="list-group-item"><i class="fas fa-info-circle mr-2"></i><strong>Tentang Aplikasi:</strong> Informasi pengembang dan lisensi</li>
  <li class="list-group-item"><i class="fas fa-sign-out-alt mr-2"></i><strong>Keluar:</strong> Logout dari sistem</li>
</ul>


      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Tutup</button>
      </div>


    </div>
  </div>
</div>


<!-- Modal Catatan Kerja -->
<div class="modal fade" id="catatanModal" tabindex="-1" role="dialog" aria-labelledby="catatanModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-lg" role="document" style="max-width: 700px;">
    <div class="modal-content">
      <div class="modal-header bg-warning text-dark">
        <h5 class="modal-title" id="catatanModalLabel"><i class="fas fa-pen-square"></i> Log Book</h5>
        <button type="button" class="close text-dark" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>

      <form id="formCatatan" method="POST" action="simpan_catatan.php">
        <div class="modal-body">
          <div class="form-group">
            <label for="judul">Judul Catatan</label>
            <input type="text" class="form-control" id="judul" name="judul" placeholder="Masukkan judul catatan" required>
          </div>
          <div class="form-group">
            <label for="isi">Isi Catatan</label>
            <textarea class="form-control" id="isi" name="isi" rows="5" placeholder="Tuliskan isi catatan kerja..." required></textarea>
          </div>
        </div>

        <div class="modal-footer">
          <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
          <button type="submit" class="btn btn-warning btn-sm"><i class="fas fa-save"></i> Simpan Catatan</button>
        </div>
      </form>
    </div>
  </div>
</div>



<!-- Jam Digital Script -->

<!-- jQuery (wajib sebelum Bootstrap JS) -->
<!-- jQuery & Bootstrap -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
<script src="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/js/bootstrap.min.js"></script>
<link href="https://stackpath.bootstrapcdn.com/bootstrap/4.6.2/css/bootstrap.min.css" rel="stylesheet">


<!-- Jam Digital -->
<script>
  function updateJamDigital() {
    const hari = ["Minggu", "Senin", "Selasa", "Rabu", "Kamis", "Jumat", "Sabtu"];
    const bulan = ["Januari", "Februari", "Maret", "April", "Mei", "Juni", "Juli", "Agustus", "September", "Oktober", "November", "Desember"];
    const now = new Date();
    const dayName = hari[now.getDay()];
    const day = String(now.getDate()).padStart(2, '0');
    const month = bulan[now.getMonth()];
    const year = now.getFullYear();
    const hours = String(now.getHours()).padStart(2, '0');
    const minutes = String(now.getMinutes()).padStart(2, '0');
    const seconds = String(now.getSeconds()).padStart(2, '0');
    const fullTime = `${dayName}, ${day} ${month} ${year} - ${hours}:${minutes}:${seconds} WIB`;
    document.getElementById('jam-digital').textContent = fullTime;
  }
  setInterval(updateJamDigital, 1000);
  updateJamDigital();
</script>

<script>
  $(document).ready(function () {
    // Inisialisasi
    $('[data-toggle="tooltip"]').tooltip();

    $(document).on('hidden.bs.modal', function () {
      if (!$('.modal.show').length) {
        $('.modal-backdrop').remove();
        $('body').removeClass('modal-open').css('padding-right', '');
      }
    });

    // Modal chat dibuka
    $('#pesanModal').on('shown.bs.modal', function () {
      const selected = $('#pilih_pengguna').val();
      if (selected) {
        $('#formChat').show();
        loadChat(selected);
      } else {
        $('#formChat').hide();
        $('#chat-body').html('<em class="text-muted">Pilih pengguna untuk mulai chat...</em>');
      }
    });

    // Submit kirim pesan
    $('#formChat').on('submit', function (e) {
      e.preventDefault();
      const pesan = $('input[name="pesan"]').val().trim();
      const ke_id = $('#penerima_id').val();
      const dari_id = "<?= $_SESSION['user_id'] ?? 0; ?>";
      const nama_pengirim = "<?= $_SESSION['nama'] ?? 'Anda'; ?>";

      if (!pesan || !ke_id) return;

      $.post('kirim_pesan.php', $(this).serialize(), function () {
        // Kirim ke WebSocket
        const data = {
          dari_id: dari_id,
          ke_id: ke_id,
          nama_pengirim: nama_pengirim,
          pesan: pesan
        };
        ws.send(JSON.stringify(data));

        // Tambahkan bubble kanan secara langsung (tidak perlu reload)
        const bubble = `<div class="chat-bubble-right">${pesan}</div>`;
        $('#chat-body').append(bubble);
        $('#chat-body').scrollTop($('#chat-body')[0].scrollHeight);

        $('input[name="pesan"]').val('');
      }).fail(function () {
        alert("Gagal mengirim pesan.");
      });
    });

    // Handle perubahan pengguna
    window.handleUserChange = function (select) {
      const userId = select.value;
      if (userId) {
        $('#penerima_id').val(userId);
        $('#formChat').show();
        loadChat(userId);
      } else {
        $('#penerima_id').val('');
        $('#formChat').hide();
        $('#chat-body').html('<em class="text-muted">Pilih pengguna untuk mulai chat...</em>');
      }
    };

    // Fungsi load chat
    window.loadChat = function (userId) {
      $.post('load_chat.php', { penerima_id: userId }, function (res) {
        $('#chat-body').html(res);
        $('#chat-body').scrollTop($('#chat-body')[0].scrollHeight);
      });
    };

    // Ambil daftar pengguna online (opsional)
    function loadOnlineUsers() {
      $.getJSON('get_online_users.php', function (users) {
        let html = '';
        if (users.length === 0) {
          html = '<div class="text-muted"><em>Tidak ada pengguna yang online</em></div>';
        } else {
          users.forEach(function (user) {
            html += `
              <div class="d-flex align-items-center justify-content-between py-1 px-2 rounded mb-1" style="cursor:pointer; background:#f8f9fa;" onclick="selectUser(${user.id}, '${user.nama}')">
                <div><i class="fas fa-user-circle text-primary mr-2"></i> ${user.nama}</div>
                <span class="badge badge-success" style="width:10px; height:10px; border-radius:50%;"></span>
              </div>`;
          });
        }
        $('#daftar-pengguna').html(html);
      });
    }

    window.selectUser = function (userId, userName) {
      $('#penerima_id').val(userId);
      $('#formChat').show();
      loadChat(userId);
    };

    loadOnlineUsers();
  });

  // WebSocket
  const ws = new WebSocket("ws://localhost:8081");

  ws.onopen = () => console.log("🟢 WebSocket Connected");

  ws.onmessage = function (event) {
    const data = JSON.parse(event.data);
    const currentChat = $('#penerima_id').val();

    if (data.dari_id == currentChat) {
      const bubble = `<div class="chat-bubble-left">${data.nama_pengirim}: ${data.pesan}</div>`;
      $('#chat-body').append(bubble);
      $('#chat-body').scrollTop($('#chat-body')[0].scrollHeight);
    } else {
      console.log("📨 Pesan baru dari", data.nama_pengirim);
      // Tambahkan notifikasi jika perlu
    }
  };

  ws.onerror = function (err) {
    console.error("❌ WebSocket Error:", err);
  };

  ws.onclose = function () {
    console.log("🔴 WebSocket Disconnected");
  };

  
</script>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

<?php if (isset($_SESSION['notif'])): ?>
<script>
Swal.fire({
  icon: '<?= $_SESSION['notif']['type']; ?>',
  title: '<?= $_SESSION['notif']['msg']; ?>',
  position: 'center',
  showConfirmButton: false,
  timer: 2000,
  timerProgressBar: true
});
</script>


<?php unset($_SESSION['notif']); endif; ?>
