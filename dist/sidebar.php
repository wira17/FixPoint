<?php
include 'koneksi.php';
$user_id = $_SESSION['user_id'];

// Ambil semua file_menu yang boleh diakses user ini
$allowed_files = [];
$query = "SELECT menu.file_menu FROM akses_menu 
          JOIN menu ON akses_menu.menu_id = menu.id 
          WHERE akses_menu.user_id = '$user_id'";
$result = mysqli_query($conn, $query);
while ($row = mysqli_fetch_assoc($result)) {
  $allowed_files[] = $row['file_menu'];
}
?>



<!-- sidebar.php -->
<style>
  /* Gaya untuk teks sidebar */
  .main-sidebar,
  .main-sidebar a,
  .main-sidebar .menu-header,
  .main-sidebar .nav-link,
  .main-sidebar span,
  .main-sidebar i {
    color: #000 !important;
  }

  /* Untuk memastikan ikon tidak kehilangan warna */
  .main-sidebar i {
    color: #000 !important;
  }

  /* Agar teks tombol footer tetap terbaca */
  .hide-sidebar-mini .btn {
    color: #fff !important;
  }
</style>




<div class="main-sidebar sidebar-style-2">
  <aside id="sidebar-wrapper">
    <div class="sidebar-brand">
      <a href="dashboard.php">F.I.X.P.O.I.N.T</a>
    </div>
    <div class="sidebar-brand sidebar-brand-sm">
      <a href="dashboard.php">FP</a>
    </div>

   <div class="p-3">
  <input type="text" class="form-control form-control-sm" id="searchMenu" placeholder="Cari menu...">
</div>

<ul class="sidebar-menu" id="menuList">
  <!-- DASHBOARD -->
  <li class="menu-header">DASHBOARD</li>
  <li class="dropdown">
    <a href="#" class="nav-link has-dropdown">
      <i class="fas fa-fire"></i> <span>DASHBOARD</span>
    </a>
    <ul class="dropdown-menu">
      <?php if (in_array('dashboard.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="dashboard.php">
          <i class="fas fa-tachometer-alt"></i> <span>Dashboard</span>
        </a>
      </li>
      <?php endif; ?>

      <?php if (in_array('dashboard2.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="dashboard2.php">
          <i class="fas fa-tachometer-alt"></i> <span>Dashboard Direktur</span>
        </a>
      </li>
      <?php endif; ?>
    </ul>
  </li>

 <!-- IT DEPARTEMEN -->
<li class="menu-header">IT DEPARTEMEN</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown"><i class="fas fa-desktop" style="color:#007bff;"></i> <span>IT DEPARTEMEN</span></a>
  <ul class="dropdown-menu">

  <?php if (in_array('data_tiket_it_hardware.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="data_tiket_it_hardware.php">
      <i class="fas fa-cogs" style="color:#28a745;"></i> <span>Data Tiket IT Hard</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('data_tiket_it_software.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="data_tiket_it_software.php">
      <i class="fas fa-code" style="color:#17a2b8;"></i> <span>Data Tiket IT Soft</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('data_off_duty.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="data_off_duty.php">
      <i class="fas fa-calendar-times" style="color:#ffc107;"></i> <span>Data Off-Duty</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('off_duty.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="off_duty.php">
      <i class="fas fa-user-slash" style="color:#dc3545;"></i> <span>Off-Duty</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('order_tiket_it_hardware.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="order_tiket_it_hardware.php">
      <i class="fas fa-ticket-alt" style="color:#6f42c1;"></i> <span>Tiket IT Hard</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('order_tiket_it_software.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="order_tiket_it_software.php">
      <i class="fas fa-ticket-alt" style="color:#20c997;"></i> <span>Tiket IT Soft</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('handling_time.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="handling_time.php">
      <i class="fas fa-stopwatch" style="color:#e83e8c;"></i> <span>Handling Time</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('spo_it.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="spo_it.php">
      <i class="fas fa-file-alt" style="color:#007bff;"></i> <span>SPO IT</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('input_spo_it.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="input_spo_it.php">
      <i class="fas fa-file-signature" style="color:#28a745;"></i> <span>Input SPO IT</span>
    </a>
  </li>
  <?php endif; ?>

    <?php if (in_array('data_permintaan_hapus_data_simrs.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="data_permintaan_hapus_data_simrs.php">
      <i class="fas fa-file-alt" style="color:#007bff;"></i> <span>Permintaan Hapus Data</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('berita_acara_it.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="berita_acara_it.php">
      <i class="fas fa-scroll" style="color:#fd7e14;"></i> <span>Berita Acara</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('data_barang_it.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="data_barang_it.php">
      <i class="fas fa-boxes" style="color:#6c757d;"></i> <span>Data Barang IT</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('maintenance_rutin.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="maintenance_rutin.php">
      <i class="fas fa-sync-alt" style="color:#6610f2;"></i> <span>Maintenance Rutin</span>
    </a>
  </li>
  <?php endif; ?>

  <?php if (in_array('koneksi_bridging.php', $allowed_files)): ?>
  <li>
    <a class="nav-link" href="koneksi_bridging.php">
      <i class="fas fa-link" style="color:#20c997;"></i> <span>Koneksi Bridging</span>
    </a>
  </li>
  <?php endif; ?>

  </ul>
</li>


<!-- SARPRAS -->
<li class="menu-header">SARPRAS</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown">
    <i class="fa fa-wrench"></i> <span>SARPRAS</span>
  </a>

  <ul class="dropdown-menu">

    <?php if (in_array('order_tiket_sarpras.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="order_tiket_sarpras.php">
        <i class="fas fa-ticket-alt"></i> <span>Order Tiket Sarpras</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('data_tiket_sarpras.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_tiket_sarpras.php">
        <i class="fas fa-clipboard-list"></i> <span>Data Tiket</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('handling_time_sarpras.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="handling_time_sarpras.php">
        <i class="fas fa-stopwatch"></i> <span>Handling Time</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('data_barang_ac.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_barang_ac.php">
        <i class="fas fa-boxes"></i> <span>Barang Sarpras</span>
      </a>
    </li>
    <?php endif; ?>

  <?php if (in_array('maintanance_rutin_sarpras.php', $allowed_files)): ?>
<li>
  <a class="nav-link" href="maintanance_rutin_sarpras.php">
    <i class="fas fa-cogs"></i> <span>Maintenance</span>
  </a>
</li>
<?php endif; ?>


  </ul>
</li>


<!-- SURAT -->
<li class="menu-header">SURAT</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown">
    <i class="fas fa-envelope-open-text"></i> <span>SURAT</span>
  </a>

  <ul class="dropdown-menu">


        <?php if (in_array('izin_keluar.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="izin_keluar.php">
        <i class="fas fa-door-open"></i> <span>Izin Keluar</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('pengajuan_cuti.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="pengajuan_cuti.php">
        <i class="fas fa-file-signature"></i> <span>Pengajuan Cuti</span>
      </a>
    </li>
    <?php endif; ?>

     <?php if (in_array('hapus_data.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="hapus_data.php">
        <i class="fas fa-file-signature"></i> <span>Hapus Data SIMRS</span>
      </a>
    </li>
    <?php endif; ?>




        <?php if (in_array('exit_clearance.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="exit_clearance.php">
        <i class="fas fa-sign-out-alt"></i> <span>Exit Clearance</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('ganti_jadwal_dinas.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="ganti_jadwal_dinas.php">
        <i class="fas fa-calendar-plus"></i> <span>Ganti Jadwal</span>
      </a>
    </li>
    <?php endif; ?>






     <?php if (in_array('master_no_surat.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="master_no_surat.php">
        <i class="fas fa-calendar-plus"></i> <span>Nomor Surat</span>
      </a>
    </li>
    <?php endif; ?>
  </ul>
</li>



 <!-- HR / SDM -->
<li class="menu-header">HR / SDM</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown"><i class="fas fa-users-cog"></i> <span>HR / SDM</span></a>
  <ul class="dropdown-menu">



 <?php if (in_array('data_cuti_delegasi.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_cuti_delegasi.php">
        <i class="fas fa-user-friends"></i> <span>Acc Cuti Delegasi</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('data_cuti_atasan.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_cuti_atasan.php">
        <i class="fas fa-user-tie"></i> <span>Acc Cuti Atasan</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('data_cuti_hrd.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_cuti_hrd.php">
        <i class="fas fa-user-shield"></i> <span>Acc Cuti HRD</span>
      </a>
    </li>
    <?php endif; ?>


       <?php if (in_array('acc_keluar_atasan.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="acc_keluar_atasan.php">
        <i class="fas fa-user-tie"></i> <span>ACC Keluar Atasan</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('acc_keluar_sdm.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="acc_keluar_sdm.php">
        <i class="fas fa-user-check"></i> <span>ACC Keluar SDM</span>
      </a>
    </li>
    <?php endif; ?>

 <?php if (in_array('data_cuti.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_cuti.php">
        <i class="fas fa-database"></i> <span>Data Cuti</span>
      </a>
    </li>
    <?php endif; ?>


        <?php if (in_array('data_izin_keluar.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_izin_keluar.php">
        <i class="fas fa-clipboard-check"></i> <span>Data Izin Keluar</span>
      </a>
    </li>
    <?php endif; ?>

      <?php if (in_array('rekap_catatan_kerja.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="rekap_catatan_kerja.php">
        <i class="fas fa-clipboard-list"></i> <span>Rekap Kerja</span>
      </a>
    </li>
    <?php endif; ?>



 <?php if (in_array('master_cuti.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="master_cuti.php">
        <i class="fas fa-calendar-alt"></i> <span>Master Cuti</span>
      </a>
    </li>
    <?php endif; ?>

 <?php if (in_array('jatah_cuti.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="jatah_cuti.php">
        <i class="fas fa-calendar-check"></i> <span>Jatah Cuti</span>
      </a>
    </li>
<?php endif; ?>








    <?php if (in_array('data_karyawan.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_karyawan.php">
        <i class="fas fa-id-badge"></i> <span>Data Karyawan</span>
      </a>
    </li>
    <?php endif; ?>

  </ul>
</li>




<!-- SIMRS -->
<li class="menu-header">LAPORAN SIMRS</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown">
    <i class="fas fa-hospital"></i> <span>LAPORAN SIMRS</span>
  </a>
  <ul class="dropdown-menu">

    <?php if (in_array('semua_antrian.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="semua_antrian.php">
        <i class="fas fa-chart-line"></i> <span>% Semua Antrian</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('mjkn_antrian.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="mjkn_antrian.php">
        <i class="fas fa-mobile-alt"></i> <span>% Antrian JKN</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('poli_antrian.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="poli_antrian.php">
        <i class="fas fa-user-md"></i> <span>% Antrian Poli</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('erm.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="erm.php">
        <i class="fas fa-file-medical"></i> <span>E-RM</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('satu_sehat.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="satu_sehat.php">
        <i class="fas fa-heartbeat"></i> <span>Satu Sehat</span>
      </a>
    </li>
    <?php endif; ?>

      <?php if (in_array('progres_kerja.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="progres_kerja.php">
        <i class="fas fa-heartbeat"></i> <span>Progres Kerja</span>
      </a>
    </li>
    <?php endif; ?>

       <?php if (in_array('slide_pelaporan.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="slide_pelaporan.php">
        <i class="fas fa-heartbeat"></i> <span>Slide Laporan</span>
      </a>
    </li>
    <?php endif; ?>

  </ul>
</li>




<!-- KOMITE KEPERAWATAN -->
<li class="menu-header">KOMITE KEPERAWATAN</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown">
    <i class="fas fa-user-md"></i> <span>KOMKEP</span>
  </a>
  <ul class="dropdown-menu">

     <?php if (in_array('kredensial.php', $allowed_files)): ?>
    <li class="dropdown">
      <a href="#" class="nav-link has-dropdown">
        <i class="fas fa-chart-bar"></i> <span>Kredensial</span>
      </a>
      <ul class="dropdown-menu">

        <?php if (in_array('praktek.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="praktek.php">
            <i class="fas fa-briefcase-medical"></i> <span>Praktek</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('wawancara.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="wawancara.php">
            <i class="fas fa-comments"></i> <span>Wawancara</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('ujian_tertulis.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="ujian_tertulis.php">
            <i class="fas fa-edit"></i> <span>Tertulis</span>
          </a>
        </li>
        <?php endif; ?>

      </ul>
    </li>
    <?php endif; ?>


   
  <?php if (in_array('hasil_kredensial.php', $allowed_files)): ?>
    <li class="dropdown">
      <a href="#" class="nav-link has-dropdown">
        <i class="fas fa-chart-bar"></i> <span>Hasil Kredensial</span>
      </a>
      <ul class="dropdown-menu">

        <?php if (in_array('hasil_praktek.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="hasil_praktek.php">
            <i class="fas fa-briefcase-medical"></i> <span>Praktek</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('hasil_wawancara.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="hasil_wawancara.php">
            <i class="fas fa-comments"></i> <span>Wawancara</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('hasil_ujian.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="hasil_ujian.php">
            <i class="fas fa-edit"></i> <span>Tertulis</span>
          </a>
        </li>
        <?php endif; ?>

      </ul>
    </li>
    <?php endif; ?>


    <?php if (in_array('kegiatan_komite.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="kegiatan_komite.php">
        <i class="fas fa-calendar-alt"></i> <span>Kegiatan Komite</span>
      </a>
    </li>
    <?php endif; ?>

   

    <?php if (in_array('laporan_komite.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="laporan_komite.php">
        <i class="fas fa-file-alt"></i> <span>Laporan Komite</span>
      </a>
    </li>
    <?php endif; ?>


       <?php if (in_array('judul.php', $allowed_files)): ?>
    <li class="dropdown">
      <a href="#" class="nav-link has-dropdown">
        <i class="fas fa-chart-bar"></i> <span>Master Data</span>
      </a>
      <ul class="dropdown-menu">

        <?php if (in_array('judul_soal.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="judul_soal.php">
            <i class="fas fa-briefcase-medical"></i> <span>Judul Soal</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('input_soal.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="input_soal.php">
            <i class="fas fa-comments"></i> <span>Input Soal</span>
          </a>
        </li>
        <?php endif; ?>


          <?php if (in_array('master_komponen_praktek.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="master_komponen_praktek.php">
            <i class="fas fa-comments"></i> <span>Komponen Ujian Praktek</span>
          </a>
        </li>
        <?php endif; ?>

           <?php if (in_array('data_anggota_komite.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="data_anggota_komite.php">
            <i class="fas fa-comments"></i> <span>Anggota Komite</span>
          </a>
        </li>
        <?php endif; ?>

    <?php if (in_array('jenis_kredensial.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="jenis_kredensial.php">
            <i class="fas fa-comments"></i> <span>Jenis Kredensial</span>
          </a>
        </li>
        <?php endif; ?>

         <?php if (in_array('jabatan_komite.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="jabatan_komite.php">
            <i class="fas fa-comments"></i> <span>Jabatan Komite</span>
          </a>
        </li>
        <?php endif; ?>
    
      </ul>
    </li>
    <?php endif; ?>


  </ul>
</li>


  


<!-- INDIKATOR MUTU -->
<li class="menu-header">INDIKATOR MUTU</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown">
    <i class="fas fa-chart-line"></i> <span>INDIKATOR MUTU</span>
  </a>
  <ul class="dropdown-menu">

    <?php if (in_array('master_indikator.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="master_indikator.php">
        <i class="fas fa-globe"></i> <span>Master IMN</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('master_indikator_rs.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="master_indikator_rs.php">
        <i class="fas fa-hospital"></i> <span>Master IMUT RS</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('master_indikator_unit.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="master_indikator_unit.php">
        <i class="fas fa-building"></i> <span>Master IMUT Unit</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('input_harian.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="input_harian.php">
        <i class="fas fa-keyboard"></i> <span>Input Imut Harian</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('capaian_imut.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="capaian_imut.php">
        <i class="fas fa-chart-bar"></i> <span>Capaian Imut</span>
      </a>
    </li>
    <?php endif; ?>

  </ul>
</li>




<!-- DOKUMEN AKREDITASI -->
<li class="menu-header">AKREDITASI</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown">
    <i class="fas fa-folder-open"></i> <span>FILE AKREDITASI</span>
  </a>
  <ul class="dropdown-menu">

    <?php if (in_array('data_dokumen.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_dokumen.php">
        <i class="fas fa-file-alt"></i> <span>Data Dokumen</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('input_dokumen.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="input_dokumen.php">
        <i class="fas fa-plus"></i> <span>Input Dokumen</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('master_pokja.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="master_pokja.php">
        <i class="fas fa-database"></i> <span>Master Pokja</span>
      </a>
    </li>
    <?php endif; ?>

  </ul>
</li>





<!-- KEUANGAN -->
<li class="menu-header">KEUANGAN</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown">
    <i class="fas fa-wallet"></i> <span>KEUANGAN</span>
  </a>
  <ul class="dropdown-menu">

    <!-- Transaksi Gaji -->
      <?php if (in_array('input_gaji.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="input_gaji.php">
            <i class="fas fa-user-clock"></i> <span>Transaksi Gaji</span>
          </a>
        </li>
        <?php endif; ?>

    <!-- Data Gaji -->
    <?php if (in_array('data_gaji.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="data_gaji.php">
            <i class="fas fa-user-clock"></i> <span>Data Gaji</span>
          </a>
        </li>
        <?php endif; ?>

    <!-- Submenu Penerimaan -->
    <li class="dropdown">
      <a href="#" class="nav-link has-dropdown">
        <i class="fas fa-arrow-down"></i> <span>Penerimaan</span>
      </a>
      <ul class="dropdown-menu">
        <?php if (in_array('masa_kerja.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="masa_kerja.php">
            <i class="fas fa-user-clock"></i> <span>Masa Kerja</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('kesehatan.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="kesehatan.php">
            <i class="fas fa-heartbeat"></i> <span>Kesehatan</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('fungsional.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="fungsional.php">
            <i class="fas fa-user-tag"></i> <span>Fungsional</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('struktural.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="struktural.php">
            <i class="fas fa-sitemap"></i> <span>Struktural</span>
          </a>
        </li>
        <?php endif; ?>

        <?php if (in_array('gaji_pokok.php', $allowed_files)): ?>
        <li>
          <a class="nav-link" href="gaji_pokok.php">
            <i class="fas fa-coins"></i> <span>Gaji Pokok</span>
          </a>
        </li>
        <?php endif; ?>
      </ul>
    </li>

    <!-- Submenu Potongan -->
    <li class="dropdown">
      <a href="#" class="nav-link has-dropdown">
        <i class="fas fa-arrow-up"></i> <span>Potongan</span>
      </a>
      <ul class="dropdown-menu">

        <?php if (in_array('potongan_bpjs_kes.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="potongan_bpjs_kes.php">
        <i class="fas fa-heartbeat"></i> <span>BPJS Kesehatan</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('potongan_bpjs_jht.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="potongan_bpjs_jht.php">
        <i class="fas fa-hand-holding-usd"></i> <span>BPJS TK JHT</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('potongan_bpjs_tk_jp.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="potongan_bpjs_tk_jp.php">
        <i class="fas fa-briefcase-medical"></i> <span>BPJS TK JP</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('potongan_dana_sosial.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="potongan_dana_sosial.php">
        <i class="fas fa-donate"></i> <span>Dana Sosial</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('pph21.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="pph21.php">
        <i class="fas fa-receipt"></i> <span>PPH21</span>
      </a>
    </li>
    <?php endif; ?>
        
      </ul>
    </li>

  </ul>
</li>


<!-- KESEKTARIATAN -->
<li class="menu-header">KESEKTARIATAN</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown">
    <i class="fas fa-building"></i> <span>KESEKTARIATAN</span>
  </a>
  <ul class="dropdown-menu">

    <?php if (in_array('surat_masuk.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="surat_masuk.php">
        <i class="fas fa-envelope-open-text"></i> <span>Surat Masuk</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('surat_keluar.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="surat_keluar.php">
        <i class="fas fa-paper-plane"></i> <span>Surat Keluar</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('arsip_digital.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="arsip_digital.php">
        <i class="fas fa-archive"></i> <span>Arsip Digital</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('agenda_direktur.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="agenda_direktur.php">
        <i class="fas fa-calendar-alt"></i> <span>Agenda Direktur</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('lihat_agenda.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="lihat_agenda.php">
        <i class="fas fa-calendar-check"></i> <span>Lihat Agenda</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('kategori_arsip.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="kategori_arsip.php">
        <i class="fas fa-folder-open"></i> <span>Kategori Arsip</span>
      </a>
    </li>
    <?php endif; ?>

  </ul>
</li>



<!-- LAPORAN KERJA -->
<li class="menu-header">LAPORAN KERJA</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown">
    <i class="fas fa-clipboard-list"></i> <span>LAPORAN KERJA</span>
  </a>
  <ul class="dropdown-menu">

      <?php if (in_array('catatan_kerja.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="catatan_kerja.php">
        <i class="fas fa-users"></i> <span>Catatan Kerja</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('laporan_harian.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="laporan_harian.php">
        <i class="fas fa-calendar-check"></i> <span>Laporan Harian</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('laporan_bulanan.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="laporan_bulanan.php">
        <i class="fas fa-file-alt"></i><span>Laporan Bulanan</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('laporan_tahunan.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="laporan_tahunan.php">
        <i class="fas fa-calendar-alt"></i> <span>Laporan Tahunan</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('data_laporan_harian.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_laporan_harian.php">
        <i class="fas fa-file-alt"></i> <span>Data Lap Harian</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('data_laporan_bulanan.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_laporan_bulanan.php">
        <i class="fas fa-file-invoice"></i> <span>Data Lap Bulanan</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('data_laporan_tahunan.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_laporan_tahunan.php">
        <i class="fas fa-file-archive"></i> <span>Data Lap Tahunan</span>
      </a>
    </li>
    <?php endif; ?>

         <?php if (in_array('input_kpi.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="input_kpi.php">
        <i class="fas fa-file-archive"></i> <span>Inpu KPI</span>
      </a>
    </li>
    <?php endif; ?>

      <?php if (in_array('master_kpi.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="master_kpi.php">
        <i class="fas fa-file-archive"></i> <span>Master KPI</span>
      </a>
    </li>
    <?php endif; ?>

  </ul>
</li>




  



<!-- JADWAL -->
<li class="menu-header">JADWAL</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown"><i class="fas fa-clock"></i> <span>JADWAL</span></a>
  <ul class="dropdown-menu">

        <?php if (in_array('absensi.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="absensi.php">
        <i class="fas fa-user-check"></i> <span>Absensi</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('data_absensi.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_absensi.php">
        <i class="fas fa-user-check"></i> <span>Data Absensi</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('jadwal_dinas.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="jadwal_dinas.php">
        <i class="fas fa-calendar-plus"></i> <span>Input Jadwal</span>
      </a>
    </li>
    <?php endif; ?>



    <?php if (in_array('data_jadwal.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="data_jadwal.php">
        <i class="fas fa-calendar-alt"></i> <span>Data Jadwal</span>
      </a>
    </li>
    <?php endif; ?>

    <?php if (in_array('jam_kerja.php', $allowed_files)): ?>
    <li>
      <a class="nav-link" href="jam_kerja.php">
        <i class="fas fa-business-time"></i> <span>Jam Kerja</span>
      </a>
    </li>
    <?php endif; ?>

  </ul>
</li>




<!-- MASTER DATA -->
<li class="menu-header">MASTER DATA</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown"><i class="fas fa-folder"></i> <span>MASTER DATA</span></a>
  <ul class="dropdown-menu">

    <?php if (in_array('perusahaan.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="perusahaan.php">
          <i class="fas fa-building"></i> <span>Perusahaan</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('pengguna.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="pengguna.php">
          <i class="fas fa-user-cog"></i> <span>Pengguna</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('hak_akses.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="hak_akses.php">
          <i class="fas fa-user-shield"></i> <span>Hak Akses</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('master_url.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="master_url.php">
          <i class="fas fa-link"></i> <span>Master URL</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('mail_setting.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="mail_setting.php">
          <i class="fas fa-envelope"></i> <span>Mail Settings</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('tele_setting.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="tele_setting.php">
          <i class="fab fa-telegram"></i> <span>Telegram Settings</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('wa_setting.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="wa_setting.php">
          <i class="fab fa-whatsapp"></i> <span>Whatsapp Settings</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('kategori_hardware.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="kategori_hardware.php">
          <i class="fas fa-microchip"></i> <span>Kategori Hardware</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('kategori_software.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="kategori_software.php">
          <i class="fas fa-laptop-code"></i> <span>Kategori Software</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('unit_kerja.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="unit_kerja.php">
          <i class="fas fa-sitemap"></i> <span>Unit Kerja</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('jabatan.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="jabatan.php">
          <i class="fas fa-briefcase"></i> <span>Jabatan</span>
        </a>
      </li>
    <?php endif; ?>

     <?php if (in_array('master_poliklinik.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="master_poliklinik.php">
          <i class="fas fa-briefcase"></i> <span>Poliklinik</span>
        </a>
      </li>
    <?php endif; ?>

     <?php if (in_array('master_status_karyawan.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="master_status_karyawan.php">
          <i class="fas fa-briefcase"></i> <span>Status Karyawan</span>
        </a>
      </li>
    <?php endif; ?>

  </ul>
</li>




     <!-- SETTING -->
<li class="menu-header">SETTING</li>
<li class="dropdown">
  <a href="#" class="nav-link has-dropdown"><i class="fas fa-cogs"></i> <span>SETTING</span></a>
  <ul class="dropdown-menu">

    <?php if (in_array('profile.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="profile.php">
          <i class="fas fa-user-circle"></i> <span>Akun Saya</span>
        </a>
      </li>
    <?php endif; ?>

    <?php if (in_array('profile2.php', $allowed_files)): ?>
      <li>
        <a class="nav-link" href="profile2.php">
          <i class="fas fa-key"></i> <span>Profil Saya</span>
        </a>
      </li>
    <?php endif; ?>

  </ul>
</li>

    </ul>

    <!-- Footer Button -->
    <div class="mt-4 mb-4 p-3 hide-sidebar-mini">
      <a href="#" class="btn btn-info btn-lg btn-block btn-icon-split" data-toggle="modal" data-target="#tentangModal">
        <i class="fas fa-info-circle"></i> Tentang Aplikasi
      </a>
    </div>


  </aside>
</div>

<!-- MODAL TENTANG APLIKASI -->
<style>
  .text-justify {
    text-align: justify;
  }
</style>

<div class="modal fade" id="tentangModal" tabindex="-1" role="dialog" aria-labelledby="tentangModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
    <div class="modal-content">
      <div class="modal-header bg-info text-white">
        <h5 class="modal-title" id="tentangModalLabel">
          <i class="fas fa-info-circle mr-2"></i> Tentang Aplikasi
        </h5>
        <button type="button" class="close text-white" data-dismiss="modal" aria-label="Tutup">
          <span aria-hidden="true">&times;</span>
        </button>
      </div>
      <div class="modal-body">
        <p class="text-justify">
          Aplikasi (FixPoint) ini dikembangkan untuk mendukung efektivitas kerja dan transparansi layanan Manajemen di berbagai instansi.
          Aplikasi ini dapat digunakan secara bebas tanpa dipungut biaya dalam bentuk apa pun.
        </p>
        <p class="text-justify">
          Pengguna <strong>dilarang memperjualbelikan, menggandakan</strong> untuk tujuan komersial, atau memodifikasi aplikasi ini
          untuk keuntungan pribadi tanpa izin tertulis dari pengembang. Sangat di larang untuk menghapus/mengganti tentang aplikasi dan menghapus logo bawaan aplikasi.
        </p>
        <p class="text-justify">
          Apabila Anda merasa terbantu dan ingin mendukung pengembangan aplikasi ini ke depannya, donasi Kopi untuk ngodingnya
          dapat disalurkan melalui:
        </p>
        <ul class="text-justify">
          <li><strong>Rekening :</strong> BSI – <code>7134197557</code></li>
          <li><strong>Atas Nama :</strong> M. Wira Satria Buana</li>
          <li><strong>Instansi :</strong> RS. Permata Hati Muara Bungo - Jambi</li>
          <li><strong>No. Tlp :</strong> 0821 7784 6209</li>
        </ul>
        <p class="text-justify">
          Setiap bentuk dukungan akan sangat berarti untuk pengembangan fitur lebih lanjut dan pemeliharaan sistem.
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Tutup</button>
      </div>
    </div>
  </div>
</div>


<!-- SCRIPT PENCARIAN -->
<script src="assets/modules/jquery.min.js"></script>
<script>
  $(document).ready(function () {
    $('#searchMenu').on('keyup', function () {
      var keyword = $(this).val().toLowerCase();
      $('#menuList li.dropdown').each(function () {
        var found = false;
        $(this).find('span').each(function () {
          if ($(this).text().toLowerCase().indexOf(keyword) > -1) {
            found = true;
          }
        });
        $(this).toggle(found);
      });

      // Tampilkan atau sembunyikan header sesuai hasil
      $('#menuList .menu-header').each(function () {
        var nextDropdown = $(this).nextUntil('.menu-header');
        var anyVisible = nextDropdown.filter(':visible').length > 0;
        $(this).toggle(anyVisible);
      });
    });
  });
</script>

<!-- SCRIPT PENCARIAN -->
<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.min.js"></script>
<script>
  $(document).ready(function () {
    $('#searchMenu').on('keyup', function () {
      var keyword = $(this).val().toLowerCase();
      $('#menuList li.dropdown').each(function () {
        var found = false;
        $(this).find('span').each(function () {
          if ($(this).text().toLowerCase().indexOf(keyword) > -1) {
            found = true;
          }
        });
        $(this).toggle(found);
      });

      // Tampilkan atau sembunyikan header sesuai hasil
      $('#menuList .menu-header').each(function () {
        var nextDropdown = $(this).nextUntil('.menu-header');
        var anyVisible = nextDropdown.filter(':visible').length > 0;
        $(this).toggle(anyVisible);
      });

      // Tutup semua dropdown saat pencarian aktif
      if (keyword !== '') {
        $('#menuList .dropdown-menu').show();
      } else {
        $('#menuList .dropdown-menu').hide();
      }
    });

    // FIX BACKDROP TETAP MUNCUL SETELAH MODAL DITUTUP
    $('#tentangModal').on('hidden.bs.modal', function () {
      $('body').removeClass('modal-open');
      $('.modal-backdrop').remove();
    });
  });
</script>


<!-- FIX BACKDROP TETAP MUNCUL SETELAH MODAL DITUTUP -->
<script>
  $(document).ready(function () {
    // Fix backdrop/modal-open tidak hilang
    $('#tentangModal').on('hidden.bs.modal', function () {
      $('body').removeClass('modal-open');
      $('.modal-backdrop').remove();
    });
  });
</script>

