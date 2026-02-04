<?php
include "koneksi.php";
include "navbar.php";
?>

<?php if (isset($_GET['error']) && $_GET['error'] == 'duplicate'): ?>
  <div class="alert alert-danger">Email sudah terdaftar</div>
<?php endif; ?>


<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Tambah Konselor</title>
</head>

<body>
<div id="content">
<div class="container">
  <div class="row mt-3">
    <div class="col-lg-12">
      <div class="card">
        <div class="card-header">
          Tambah Konselor
        </div>

        <div class="card-body">
          <form action="user_simpan.php" method="POST">

            <!-- Nama -->
            <div class="mb-3">
              <label class="form-label">Nama Lengkap</label>
              <input type="text"
                     name="nama_user"
                     class="form-control"
                     placeholder="Nama lengkap"
                     required>
            </div>

            <!-- Jenis Kelamin -->
            <div class="mb-3">
              <label class="form-label">Jenis Kelamin</label>
              <select name="jk_user" class="form-select" required>
                <option value="">-- Pilih --</option>
                <option value="Laki-laki">Laki-laki</option>
                <option value="Perempuan">Perempuan</option>
              </select>
            </div>

            <!-- Email -->
            <div class="mb-3">
              <label class="form-label">Email</label>
              <input type="email"
                     name="email_user"
                     class="form-control"
                     placeholder="email@domain.com"
                     required>
            </div>

            <!-- Password -->
            <div class="mb-3">
            <label class="form-label">Password</label>

            <div class="input-group">
                <input type="password"
                    name="pass_user"
                    id="password"
                    class="form-control"
                    required>

                <button class="btn btn-outline-secondary"
                        type="button"
                        id="togglePassword">
                👁
                </button>
            </div>
            </div>


            <!-- Role -->
            <input type="hidden" name="role" value="konselor">

            <div class="mb-3">
              <label class="form-label">Role</label>
              <input type="text" class="form-control" value="Konselor" readonly>
            </div>

            <button type="submit" class="btn btn-primary">
              Simpan User
            </button>

          </form>
        </div>
      </div>
    </div>
  </div>
</div>
</div>


<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.5/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const toggleBtn = document.getElementById("toggleSidebar");
  const sidebar = document.getElementById("sidebar");
  const content = document.getElementById("content");

  toggleBtn.addEventListener("click", () => {
    sidebar.classList.toggle("active");
    content.classList.toggle("shift");
  });
</script>

<script>
  const togglePassword = document.getElementById("togglePassword");
  const passwordInput = document.getElementById("password");

  togglePassword.addEventListener("click", function () {
    const type = passwordInput.type === "password" ? "text" : "password";
    passwordInput.type = type;

    // Optional: ganti icon
    this.textContent = type === "password" ? "👁" : "🙈";
  });
</script>

</body>
</html>


