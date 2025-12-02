<?php

// INCLUDE DAN DEFINISI CLASS LIBRARY

include("config.php");

/**
* Class Database (Dari database.php)
**/
class Database {
    protected $host;
    protected $user;
    protected $password;
    protected $db_name;
    protected $conn;

    public function __construct() {
        $this->getConfig();
        global $config; 
        $this->host = $config['host'];
        $this->user = $config['username'];
        $this->password = $config['password'];
        $this->db_name = $config['db_name'];

        $this->conn = new mysqli($this->host, $this->user, $this->password, $this->db_name);
        if ($this->conn->connect_error) {
            die("Connection failed: " . $this->conn->connect_error);
        }
    }

    private function getConfig() {
        global $config; 
    }

    public function query($sql) {
        return $this->conn->query($sql);
    }

    public function get($table, $where=null) {
        if ($where) {
            $where = " WHERE ".$where;
        }
        $sql = "SELECT * FROM ".$table.$where;
        $result = $this->conn->query($sql);
        if ($result && $result->num_rows > 0) {
            return $result->fetch_assoc();
        }
        return false;
    }

    public function insert($table, $data) {
        $column = [];
        $value = [];
        if (is_array($data)) {
            foreach($data as $key => $val) {
                $column[] = $key;
                $value[] = "'".$this->conn->real_escape_string($val)."'";
            }
            $columns = implode(",", $column);
            $values = implode(",", $value);
        }
        $sql = "INSERT INTO ".$table." (".$columns.") VALUES (".$values.")";
        return $this->conn->query($sql);
    }

    public function update($table, $data, $where) {
        $update_value = [];
        if (is_array($data)) {
            foreach($data as $key => $val) {
                $update_value[] = "$key='".$this->conn->real_escape_string($val)."'";
            }
            $update_value = implode(",", $update_value);
        }
        $sql = "UPDATE ".$table." SET ".$update_value." WHERE ".$where;
        return $this->conn->query($sql);
    }

    public function delete($table, $filter) {
        $sql = "DELETE FROM ".$table." ".$filter;
        return $this->conn->query($sql);
    }
}

/**
* Class Form
* Ditambahkan parameter type dan value untuk mendukung CRUD Data Barang
**/
class Form
{
    private $fields = array();
    private $action;
    private $submit = "Submit Form";
    private $jumField = 0;

    public function __construct($action, $submit)
    {
        $this->action = $action;
        $this->submit = $submit;
    }

    public function displayForm()
    {
        echo "<form action='".$this->action."' method='POST' enctype='multipart/form-data'>";
        echo '<table width="100%" border="0">';
        for ($j=0; $j<count($this->fields); $j++) {
            echo"<tr><td align='right'>".$this->fields[$j]['label']."</td>";
            echo"<td>";
            
            $type = $this->fields[$j]['type'];
            $name = $this->fields[$j]['name'];
            $value = $this->fields[$j]['value'];

            if ($type == 'file') {
                echo "<input type='file' name='".$name."'>";
            } else {
                echo "<input type='text' name='".$name."' value='".$value."'>";
            }

            echo "</td></tr>";
        }
        echo "<tr><td colspan='2'>";
        echo "<input type='submit' name='".$this->submit."' value='Simpan'></td></tr>"; 
        echo "</table>";
        echo "</form>";
    }

    public function addField($name, $label, $type = "text", $value = "")
    {
        $this->fields [$this->jumField]['name'] = $name;
        $this->fields [$this->jumField]['label'] = $label;
        $this->fields [$this->jumField]['type'] = $type;
        $this->fields [$this->jumField]['value'] = $value;
        $this->jumField ++;
    }
}

// LOGIKA CRUD (HAPUS, TAMBAH, UBAH)


$db = new Database();

$action = $_GET['action'] ?? 'list';
$id = $_GET['id'] ?? null;

if ($action == 'hapus' && $id) {
    if ($db->delete("data_barang", "WHERE id_barang = " . (int)$id)) {
        header('Location: index.php?status=deleted');
        exit;
    } else {
        $message = "Gagal menghapus data.";
    }
}

if (isset($_POST['submit_tambah'])) {
    $nama = $_POST['nama'];
    $kategori = $_POST['kategori'];
    $harga_jual = $_POST['harga_jual'];
    $harga_beli = $_POST['harga_beli'];
    $stok = $_POST['stok'];
    $gambar = null;
    
    if ($_FILES['file_gambar']['error'] == 0) {
        $file_gambar = $_FILES['file_gambar'];
        $filename = str_replace(' ', '_', $file_gambar['name']);
        $destination = dirname(__FILE__) . '/gambar/' . $filename; 
        if (move_uploaded_file($file_gambar['tmp_name'], $destination)) {
            $gambar = $filename;
        }
    }

    $data = [
        'nama' => $nama, 'kategori' => $kategori, 'harga_jual' => $harga_jual, 
        'harga_beli' => $harga_beli, 'stok' => $stok, 'gambar' => $gambar
    ];

    if ($db->insert('data_barang', $data)) {
        header('Location: index.php?status=added');
        exit;
    } else {
        $message = "Gagal menambah data.";
    }
}

if (isset($_POST['submit_ubah'])) {
    $id_update = $_POST['id_barang'];
    $data = [
        'nama' => $_POST['nama'], 'kategori' => $_POST['kategori'], 'harga_jual' => $_POST['harga_jual'], 
        'harga_beli' => $_POST['harga_beli'], 'stok' => $_POST['stok']
    ];
    
    if ($_FILES['file_gambar']['error'] == 0 && $_FILES['file_gambar']['size'] > 0) {
        $file_gambar = $_FILES['file_gambar'];
        $filename = str_replace(' ', '_', $file_gambar['name']);
        $destination = dirname(__FILE__) . '/gambar/' . $filename;
        if (move_uploaded_file($file_gambar['tmp_name'], $destination)) {
             $data['gambar'] = $filename;
        }
    }

    if ($db->update("data_barang", $data, "id_barang = " . (int)$id_update)) {
        header('Location: index.php?status=updated');
        exit;
    } else {
        $message = "Gagal mengupdate data.";
    }
}

// HEADER

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Praktikum 10 - OOP Modular Terpusat</title>
    <style>
        body { font-family: sans-serif; margin: 20px; }
        table { border-collapse: collapse; width: 100%; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        nav a { margin-right: 10px; text-decoration: none; color: blue; }
        .container { width: 800px; margin: auto; }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>Database Barang</h1>
        </header>
        <nav>
            <a href="index.php?action=list">Home (Daftar Barang)</a>
            <a href="index.php?action=tambah">Tambah Barang</a>
            <a href="form_input.php?action=tambah">Isi Form</a>
            <a href="mobil.php?action=tambah">Mobil</a>
        </nav>
        <hr>
<?php 
if (isset($message)) {
    echo "<p style='color:red;'>$message</p>";
}

// VIEW

if ($action == 'tambah'):
    echo "<h2>Tambah Barang</h2>";
    $form_tambah = new Form("index.php", "submit_tambah"); 
    $form_tambah->addField("nama", "Nama Barang");
    $form_tambah->addField("kategori", "Kategori");
    $form_tambah->addField("harga_jual", "Harga Jual");
    $form_tambah->addField("harga_beli", "Harga Beli");
    $form_tambah->addField("stok", "Stok");
    $form_tambah->addField("file_gambar", "Gambar", "file");
    $form_tambah->displayForm();

elseif ($action == 'ubah' && $id):
    $data_barang = $db->get("data_barang", "id_barang = " . (int)$id);
    if (!$data_barang) {
        echo "<p>Data barang tidak ditemukan.</p>";
    } else {
        echo "<h2>Ubah Barang</h2>";
        $form_ubah = new Form("index.php", "submit_ubah"); 
        $form_ubah->addField("id_barang", "", "hidden", $data_barang['id_barang']);
        $form_ubah->addField("nama", "Nama Barang", "text", $data_barang['nama']);
        $form_ubah->addField("kategori", "Kategori", "text", $data_barang['kategori']);
        $form_ubah->addField("harga_jual", "Harga Jual", "text", $data_barang['harga_jual']);
        $form_ubah->addField("harga_beli", "Harga Beli", "text", $data_barang['harga_beli']);
        $form_ubah->addField("stok", "Stok", "text", $data_barang['stok']);
        $form_ubah->addField("file_gambar", "Gambar (Kosongkan jika tidak ganti)", "file");
        $form_ubah->displayForm();
    }

else:
    echo "<h2>Daftar Barang</h2>";
    $sql = "SELECT * FROM data_barang";
    $result = $db->query($sql);
    ?>
    <table>
        <tr>
            <th>Gambar</th>
            <th>Nama Barang</th>
            <th>Kategori</th>
            <th>Harga Jual</th>
            <th>Harga Beli</th>
            <th>Stok</th>
            <th>Aksi</th>
        </tr>
        <?php
        if ($result && $result->num_rows > 0) {
            while ($row = $result->fetch_assoc()) {
                echo "<tr>";
                echo "<td><img src='gambar/" . $row['gambar'] . "' width='50'></td>";
                echo "<td>" . htmlspecialchars($row['nama']) . "</td>";
                echo "<td>" . htmlspecialchars($row['kategori']) . "</td>";
                echo "<td>" . htmlspecialchars($row['harga_jual']) . "</td>";
                echo "<td>" . htmlspecialchars($row['harga_beli']) . "</td>";
                echo "<td>" . htmlspecialchars($row['stok']) . "</td>";
                echo "<td>
                        <a href='index.php?action=ubah&id=" . $row['id_barang'] . "'>Ubah</a> | 
                        <a href='index.php?action=hapus&id=" . $row['id_barang'] . "' onclick=\"return confirm('Yakin ingin menghapus data ini?')\">Hapus</a>
                      </td>";
                echo "</tr>";
            }
        } else {
            echo "<tr><td colspan='7'>Belum ada data</td></tr>";
        }
        ?>
    </table>
    
<?php endif; ?>

<hr>

<?php 

// FOOTER

?>
        <hr>
        <footer>
            <p>&copy; 2025, Implementasi OOP dan Modularisasi Praktikum 10</p>
        </footer>
    </div>
</body>
</html>