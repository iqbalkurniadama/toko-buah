<?php
//-------------------------------------------------------------------------
//fungsi hitungbobot, menggunakan pendekatan tf.idf
function hitungbobot()
{
    $konek = mysqli_connect("localhost", "root", "", "dbstbi");
    //berapa jumlah DocId total?, n
    $query1 = "SELECT DISTINCT DocId FROM tbindex";
    $resn = mysqli_query($konek, $query1);
    $n = mysqli_num_rows($resn);
    //ambil setiap record dalam tabel tbindex
    //hitung bobot untuk setiap Term dalam setiap DocId
    $query2 = "SELECT * FROM tbindex ORDER BY Id";
    $resBobot = mysqli_query($konek, $query2);
    $num_rows = mysqli_num_rows($resBobot);
    print("Terdapat " . $num_rows . " Term yang diberikan bobot. <br />");
    while ($rowbobot = mysqli_fetch_array($resBobot)) {
        //$w = tf * log (n/N)
        $term = $rowbobot['Term'];
        $tf = $rowbobot['Count'];
        $id = $rowbobot['Id'];
        //berapa jumlah dokumen yang mengandung term tersebut?, N
        $query3 = "SELECT Count(*) as N FROM tbindex WHERE Term = '$term'";
        $resNTerm = mysqli_query($konek, $query3);
        $rowNTerm = mysqli_fetch_array($resNTerm);
        $NTerm = $rowNTerm['N'];
        $w = $tf * log($n / $NTerm);
        //update bobot dari term tersebut
        $query4 = "UPDATE tbindex SET Bobot = $w WHERE Id = $id";
        $resUpdateBobot = mysqli_query($konek, $query4);
    } //end while $rowbobot
} //end function hitungbobot

//-------------------------------------------------------------------------
//fungsi panjangvektor, jarak euclidean
//akar(penjumlahan kuadrat dari bobot setiapTerm)
$konek = mysqli_connect("localhost", "root", "", "dbstbi");
//hapus isi tabel tbvektor
$query1 = "TRUNCATE TABLE tbvektor";
mysqli_query($konek, $query1);
//ambil setiap DocId dalam tbindex
//hitung panjang vektor untuk setiap DocId tersebut
//simpan ke dalam tabel tbvektor
$query2 = "SELECT DISTINCT DocId FROM tbindex";
$resDocId = mysqli_query($konek, $query2);
$num_rows = mysqli_num_rows($resDocId);
print("Terdapat " . $num_rows . " dokumen yang dihitung panjang vektornya. <br />");

while ($rowDocId = mysqli_fetch_array($resDocId)) {
    $docId = $rowDocId['DocId'];
    $query3 = "SELECT Bobot FROM tbindex WHERE DocId = $docId";
    $resVektor = mysqli_query($konek, $query3);
    //jumlahkan semua bobot kuadrat 
    $panjangVektor = 0;
    while ($rowVektor = mysqli_fetch_array($resVektor)) {
        $panjangVektor = $panjangVektor + $rowVektor['Bobot'] * $rowVektor['Bobot'];
    }
    //hitung akarnya
    $panjangVektor = sqrt($panjangVektor);
    //masukkan ke dalam tbvektor
    $query4 = "INSERT INTO tbvektor (DocId, Panjang) VALUES ($docId, $panjangVektor)";
    $resInsertVektor = mysqli_query($konek, $query4);
} //end while $rowDocId } //end functionpanjangvektor
//------------------------------------------------------------------------- 
