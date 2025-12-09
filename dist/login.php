<?php
session_start();
require 'koneksi.php';

$notif = "";

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['email'], $_POST['password'])) {
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $captcha_input = strtoupper(trim($_POST['captcha_input'] ?? ''));
    $captcha_session = $_SESSION['captcha'] ?? '';

    if ($email === "" || $password === "") {
        $notif = "Email dan Password tidak boleh kosong.";
    } elseif ($captcha_input === "" || $captcha_input !== $captcha_session) {
        $notif = "Kode keamanan salah atau kosong.";
    } else {
        $stmt = $conn->prepare("SELECT id, nama, password_hash, status FROM users WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows === 1) {
            $stmt->bind_result($id, $nama, $password_hash, $status);
            $stmt->fetch();

            if ($status != 'active') {
                $notif = "Akun belum aktif. Hubungi admin.";
            } elseif (password_verify($password, $password_hash)) {
                $_SESSION['user_id'] = $id;
                $_SESSION['nama'] = $nama;

                $update = $conn->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
                $update->bind_param("i", $id);
                $update->execute();

                header("Location: dashboard.php");
                exit;
            } else {
                $notif = "Password salah.";
            }
        } else {
            $notif = "Email tidak ditemukan.";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>f.i.x.p.o.i.n.t</title>

  <link rel="stylesheet" href="assets/modules/bootstrap/css/bootstrap.min.css">
  <link rel="stylesheet" href="assets/modules/fontawesome/css/all.min.css">
  <link rel="stylesheet" href="assets/css/style.css">
  <link rel="stylesheet" href="assets/css/components.css">
  <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

  <style>
    /* ===== LATAR BELAKANG BLUR ===== */
    body {
      background: url('images/back2.jpg') no-repeat center center fixed;
      background-size: cover;
      display: flex;
      justify-content: center;
      align-items: center;
      height: 100vh;
      margin: 0;
      backdrop-filter: blur(6px);
      -webkit-backdrop-filter: blur(6px);
    }

    /* ===== KOTAK LOGIN ===== */
    .login-box {
      background: rgba(255, 255, 255, 0.93);
      border-radius: 20px;
      padding: 35px 40px;
      box-shadow: 0 8px 30px rgba(0,0,0,0.25);
      width: 100%;
      max-width: 700px; /* disamakan dengan modal daftar */
      animation: fadeIn 0.8s ease;
    }

    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(-20px); }
      to { opacity: 1; transform: translateY(0); }
    }

    .login-logo img {
      width: 150px;
      height: auto;
    }

    .form-group label {
      font-size: 14px;
      font-weight: 600;
    }

    .form-control {
      font-size: 14px;
      padding: 8px 10px;
    }

    .btn {
      font-size: 14px;
      padding: 10px 15px;
    }

    .text-muted {
      font-size: 13px;
    }

    @media (max-width: 768px) {
      .login-box {
        padding: 25px;
        margin: 15px;
      }
    }
  </style>
</head>

<body>

<div class="login-box">
  <div class="login-logo text-center mb-3">
    <img src="images/logo7.png" alt="Logo FixPoint">
  </div>

  <?php if (!empty($notif)): ?>
    <script>
      document.addEventListener("DOMContentLoaded", function() {
        Swal.fire({
          icon: 'error',
          title: 'Login Gagal',
          text: <?= json_encode($notif) ?>,
          confirmButtonColor: '#d33'
        });
      });
    </script>
  <?php endif; ?>

  <form method="POST" action="login.php">
    <div class="form-row">
      <div class="form-group col-md-6">
        <label for="email"><i class="fas fa-envelope text-primary"></i> Email</label>
        <input type="email" name="email" id="email" class="form-control" placeholder="Masukkan email" required>
      </div>

      <div class="form-group col-md-6">
        <label for="password"><i class="fas fa-lock text-primary"></i> Password</label>
        <div class="input-group">
          <input type="password" name="password" id="password" class="form-control" placeholder="Masukkan password" required>
          <div class="input-group-append">
            <span class="input-group-text" onclick="togglePassword('password', 'toggleIcon')" style="cursor:pointer">
              <i class="fas fa-eye" id="toggleIcon"></i>
            </span>
          </div>
        </div>
      </div>

      <div class="form-group col-md-8">
        <label for="captcha_input"><i class="fas fa-shield-alt text-primary"></i> Kode Keamanan</label>
        <div class="d-flex align-items-center mb-2">
          <img src="captcha.php" id="captcha-img" alt="Captcha" style="border-radius: 5px; height: 38px;">
          <a href="#" onclick="document.getElementById('captcha-img').src = 'captcha.php?' + Date.now(); return false;" class="ml-3">🔄 Muat Ulang</a>
        </div>
        <input type="text" name="captcha_input" id="captcha_input" class="form-control" placeholder="Masukkan kode di atas" required>
      </div>

      <div class="form-group col-md-4 d-flex align-items-end">
        <button type="submit" class="btn btn-primary btn-block shadow-sm w-100">
          <i class="fas fa-sign-in-alt mr-1"></i> Login
        </button>
      </div>
    </div>
  </form>

  <div class="text-center mt-2">
    <a href="#" data-toggle="modal" data-target="#modalForgot">
      Lupa Password?
      <i class="fas fa-question-circle text-danger" title="Cara reset password"></i>
    </a>
  </div>

  <div class="text-center mt-3">
    Belum punya akun? <a href="#" data-toggle="modal" data-target="#modalRegister">Daftar di sini</a>
  </div>

  <hr>
  <div class="text-center text-muted">
    &copy; <?= date('Y') ?> FixPoint, V. 1.0.5<br>
    Info Trouble: <strong>M. Wira</strong> - <a href="tel:+6282177856209">0821-7784-6209</a>
  </div>
</div>

<!-- MODAL REGISTER -->
<div class="modal fade" id="modalRegister" tabindex="-1" role="dialog" aria-labelledby="modalRegisterLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg" role="document">
    <form method="POST" action="proses_register.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-user-plus mr-2"></i> Daftar Akun Baru</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <div class="form-row">
          <div class="form-group col-md-6">
            <label>NIK</label>
            <input type="text" name="nik" class="form-control" required>
          </div>
          <div class="form-group col-md-6">
            <label>Nama Lengkap</label>
            <input type="text" name="nama" class="form-control" required>
          </div>
          <div class="form-group col-md-6">
            <label>Jabatan</label>
            <select name="jabatan" class="form-control" required>
              <option value="">Pilih Jabatan</option>
              <?php
              $jabatan = $conn->query("SELECT nama_jabatan FROM jabatan");
              while($r = $jabatan->fetch_assoc()):
              ?>
                <option value="<?= $r['nama_jabatan'] ?>"><?= $r['nama_jabatan'] ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group col-md-6">
            <label>Unit Kerja</label>
            <select name="unit_kerja" class="form-control" required>
              <option value="">Pilih Unit</option>
              <?php
              $unit = $conn->query("SELECT nama_unit FROM unit_kerja");
              while($r = $unit->fetch_assoc()):
              ?>
                <option value="<?= $r['nama_unit'] ?>"><?= $r['nama_unit'] ?></option>
              <?php endwhile; ?>
            </select>
          </div>
          <div class="form-group col-md-6">
            <label>Email</label>
            <input type="email" name="email" class="form-control" required>
          </div>
          <div class="form-group col-md-6">
            <label>Password</label>
            <div class="input-group">
              <input type="password" name="password" id="reg-password" class="form-control" required>
              <div class="input-group-append">
                <span class="input-group-text" onclick="togglePassword('reg-password', 'reg-eye')" style="cursor:pointer">
                  <i class="fas fa-eye" id="reg-eye"></i>
                </span>
              </div>
            </div>
          </div>
          <div class="form-group col-md-6">
            <label>Konfirmasi Password</label>
            <div class="input-group">
              <input type="password" name="konfirmasi_password" id="reg-confirm" class="form-control" required>
              <div class="input-group-append">
                <span class="input-group-text" onclick="togglePassword('reg-confirm', 'reg-confirm-eye')" style="cursor:pointer">
                  <i class="fas fa-eye" id="reg-confirm-eye"></i>
                </span>
              </div>
            </div>
          </div>
          <div class="form-group col-md-6">
            <label>Atasan Langsung</label>
            <select name="atasan_id" class="form-control">
              <option value="">Pilih Atasan</option>
              <?php
              $atasan = $conn->query("SELECT id, nama FROM users ORDER BY nama");
              while($r = $atasan->fetch_assoc()):
              ?>
                <option value="<?= $r['id'] ?>"><?= $r['nama'] ?></option>
              <?php endwhile; ?>
            </select>
          </div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-user-plus mr-1"></i> Daftar Sekarang
        </button>
      </div>
    </form>
  </div>
</div>

<!-- MODAL LUPA PASSWORD -->
<div class="modal fade" id="modalForgot" tabindex="-1" role="dialog" aria-labelledby="modalForgotLabel" aria-hidden="true">
  <div class="modal-dialog" role="document">
    <form method="POST" action="proses_forgot.php" class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title"><i class="fas fa-key mr-2"></i> Lupa Password</h5>
        <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
      </div>
      <div class="modal-body">
        <p>Masukkan email Anda untuk mengatur ulang password.</p>
        <div class="form-group mt-3">
          <label><i class="fas fa-envelope text-primary"></i> Email</label>
          <input type="email" name="email" class="form-control" placeholder="Masukkan email Anda" required>
        </div>
      </div>
      <div class="modal-footer">
        <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane mr-1"></i> Kirim Link Reset</button>
        <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/modules/jquery.min.js"></script>
<script src="assets/modules/bootstrap/js/bootstrap.bundle.min.js"></script>
<script>
function togglePassword(inputId, iconId) {
  const input = document.getElementById(inputId);
  const icon = document.getElementById(iconId);
  if (input.type === "password") {
    input.type = "text";
    icon.classList.replace("fa-eye", "fa-eye-slash");
  } else {
    input.type = "password";
    icon.classList.replace("fa-eye-slash", "fa-eye");
  }
}
</script>

</body>
</html>
