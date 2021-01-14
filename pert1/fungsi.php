<?php

include 'koneksi.php';

function preproses($teks)
{
	$konek = mysqli_connect("localhost", "root", "", "dbstbi-data");

	$teks = str_replace("'", " ", $teks);
	$teks = str_replace("-", " ", $teks);
	$teks = str_replace(")", " ", $teks);
	$teks = str_replace("(", " ", $teks);
	$teks = str_replace("\"", " ", $teks);
	$teks = str_replace("/", " ", $teks);
	$teks = str_replace("=", " ", $teks);
	$teks = str_replace(".", " ", $teks);
	$teks = str_replace(",", " ", $teks);
	$teks = str_replace(":", " ", $teks);
	$teks = str_replace(";", " ", $teks);
	$teks = str_replace("!", " ", $teks);
	$teks = str_replace("?", " ", $teks);

	$teks = strtolower(trim($teks));

	$astoplist = array(
		"yang", "juga", "dari", "dia", "kami", "kamu", "ini", "itu",
		"atau", "dan", "tersebut", "pada", "dengan", "adalah", "yaitu", "ke"
	);
	foreach ($astoplist as $i => $value) {
		$teks = str_replace($astoplist[$i], "", $teks);
	}

	$query = "SELECT * FROM tbstem ORDER BY Id";
	$restem = mysqli_query($konek, $query);

	while ($rowstem = mysqlI_fetch_array($restem)) {
		$teks = str_replace($rowstem['Term'], $rowstem['Stem'], $teks);
	}

	$teks = strtolower(trim($teks));
	return $teks;
}

function buatindex()
{
	$konek = mysqli_connect("localhost", "root", "", "dbstbi-data");
	$querycate = "TRUNCATE TABLE tbindex";
	mysqli_query($konek, $querycate);

	$query = "SELECT * FROM tbberita ORDER BY Id";
	$resBerita = mysqli_query($konek, $query);
	$num_rows = mysqli_num_rows($resBerita);
	print("Mengindeks sebanyak " . $num_rows . " berita. <br />");

	while ($row = mysqli_fetch_array($resBerita)) {
		$docId = $row['Id'];
		$berita = $row['Berita'];

		$berita = preproses($berita);

		$aberita = explode(" ", trim($berita));

		foreach ($aberita as $j => $value) {
			if ($aberita[$j] != "") {

				$query1 = "SELECT Count FROM tbindex WHERE Term = '$aberita[$j]' AND DocId = $docId";
				$rescount = mysqli_query($konek, $query1);
				$num_rows = mysqli_num_rows($rescount);

				if ($num_rows > 0) {
					$rowcount = mysqli_fetch_array($rescount);
					$count = $rowcount['Count'];
					$count++;

					$query2 = "UPDATE tbindex SET Count = $count WHERE Term = '$aberita[$j]' AND DocId = $docId";
					mysqli_query($konek, $query2);
				} else {
					$query3 = "INSERT INTO tbindex (Term, DocId, Count) VALUES ('$aberita[$j]', $docId, 1)";
					mysqli_query($konek, $query3);
				}
			}
		}
	}
}

function hitungbobot()
{
	$konek = mysqli_connect("localhost", "root", "", "dbstbi-data");

	$query1 = "SELECT DISTINCT DocId FROM tbindex";
	$resn = mysqli_query($konek, $query1);
	$n = mysqli_num_rows($resn);

	$query2 = "SELECT * FROM tbindex ORDER BY Id";
	$resBobot = mysqli_query($konek, $query2);
	$num_rows = mysqli_num_rows($resBobot);
	print("Terdapat " . $num_rows . " Term yang diberikan bobot. <br />");

	while ($rowbobot = mysqli_fetch_array($resBobot)) {
		$term = $rowbobot['Term'];
		$tf = $rowbobot['Count'];
		$id = $rowbobot['Id'];

		$query3 = "SELECT Count(*) as N FROM tbindex WHERE Term = '$term'";
		$resNTerm = mysqli_query($konek, $query3);
		$rowNTerm = mysqli_fetch_array($resNTerm);
		$NTerm = $rowNTerm['N'];

		$w = $tf * log($n / $NTerm);

		$query4 = "UPDATE tbindex SET Bobot = $w WHERE Id = $id";
		$resUpdateBobot = mysqli_query($konek, $query4);
	}
}

function panjangvektor()
{
	$konek = mysqli_connect("localhost", "root", "", "dbstbi-data");

	$query1 = "TRUNCATE TABLE tbvektor";
	mysqli_query($konek, $query1);

	$query2 = "SELECT DISTINCT DocId FROM tbindex";
	$resDocId = mysqli_query($konek, $query2);

	$num_rows = mysqli_num_rows($resDocId);
	print("Terdapat " . $num_rows . " dokumen yang dihitung panjang vektornya. <br />");

	while ($rowDocId = mysqli_fetch_array($resDocId)) {
		$docId = $rowDocId['DocId'];

		$query3 = "SELECT Bobot FROM tbindex WHERE DocId = $docId";
		$resVektor = mysqli_query($konek, $query3);

		$panjangVektor = 0;
		while ($rowVektor = mysqli_fetch_array($resVektor)) {
			$panjangVektor = $panjangVektor + $rowVektor['Bobot']  *  $rowVektor['Bobot'];
		}

		$panjangVektor = sqrt($panjangVektor);

		$query4 = "INSERT INTO tbvektor (DocId, Panjang) VALUES ($docId, $panjangVektor)";
		$resInsertVektor = mysqli_query($konek, $query4);
	}
}

function hitungsim($query)
{
	$konek = mysqli_connect("localhost", "root", "", "dbstbi-data");

	$query5 = "SELECT Count(*) as n FROM tbvektor";
	$resn = mysqli_query($konek, $query5);
	$rown = mysqli_fetch_array($resn);
	$n = $rown['n'];

	$aquery = explode(" ", $query);

	$panjangQuery = 0;
	$aBobotQuery = array();

	for ($i = 0; $i < count($aquery); $i++) {
		$query6 = "SELECT Count(*) as N from tbindex WHERE Term = '$aquery[$i]'";
		$resNTerm = mysqli_query($konek, $query6);
		$rowNTerm = mysqli_fetch_array($resNTerm);
		$NTerm = $rowNTerm['N'];
		$idf = 0;
		if ($NTerm > 0)
			$idf = log($n / $NTerm);

		$aBobotQuery[] = $idf;

		$panjangQuery = $panjangQuery + $idf * $idf;
	}

	$panjangQuery = sqrt($panjangQuery);

	$jumlahmirip = 0;

	$query7 = "SELECT * FROM tbvektor ORDER BY DocId";
	$resDocId = mysqli_query($konek, $query7);
	while ($rowDocId = mysqli_fetch_array($resDocId)) {

		$dotproduct = 0;

		$docId = $rowDocId['DocId'];
		$panjangDocId = $rowDocId['Panjang'];

		$query8 = "SELECT * FROM tbindex WHERE DocId = $docId";
		$resTerm = mysqli_query($konek, $query8);
		while ($rowTerm = mysqli_fetch_array($resTerm)) {
			for ($i = 0; $i < count($aquery); $i++) {

				if ($rowTerm['Term'] == $aquery[$i]) {
					$dotproduct = $dotproduct + $rowTerm['Bobot'] * $aBobotQuery[$i];
				}
			}
		}

		if ($dotproduct > 0) {
			$sim = $dotproduct / ($panjangQuery * $panjangDocId);

			$query9 = "INSERT INTO tbcache (Query, DocId, Value) VALUES ('$query', $docId, $sim)";
			$resInsertCache = mysqli_query($konek, $query9);
			$jumlahmirip++;
		}
	}
	if ($jumlahmirip == 0) {
		$query10 = "INSERT INTO tbcache (Query, DocId, Value) VALUES ('$query', 0, 0)";
		$resInsertCache = mysqli_query($konek, $query10);
	}
}

function ambilcache($keyword)
{
	$konek = mysqli_connect("localhost", "root", "", "dbstbi-data");

	$query11 = "SELECT *  FROM tbcache WHERE Query = '$keyword' ORDER BY Value DESC";
	$resCache = mysqli_query($konek, $query11);
	$num_rows = mysqli_num_rows($resCache);

	if ($num_rows > 0) {
		while ($rowCache = mysqli_fetch_array($resCache)) {
			$docId = $rowCache['DocId'];
			$sim = $rowCache['Value'];

			if ($docId != 0) {
				$query12 = "SELECT * FROM tbberita WHERE Id = $docId";
				$resBerita = mysqli_query($konek, $query12);
				$rowBerita = mysqli_fetch_array($resBerita);

				$judul = $rowBerita['Judul'];
				$berita = $rowBerita['Berita'];

				print($docId . ". (" . $sim . ") <font color=blue><b>" . $judul . "</b></font><br />");
				print($berita . "<hr />");
			} else {
				print("<b>Tidak ada... </b><hr />");
			}
		}
	} else {
		hitungsim($keyword);

		$query13 = "SELECT *  FROM tbcache WHERE Query = '$keyword' ORDER BY Value DESC";
		$resCache = mysqli_query($konek, $query13);
		$num_rows = mysqli_num_rows($resCache);

		while ($rowCache = mysqli_fetch_array($resCache)) {
			$docId = $rowCache['DocId'];
			$sim = $rowCache['Value'];

			if ($docId != 0) {
				$query14 = "SELECT * FROM tbberita WHERE Id = $docId";
				$resBerita = mysqli_query($konek, $query14);
				$rowBerita = mysqli_fetch_array($resBerita);

				$judul = $rowBerita['Judul'];
				$berita = $rowBerita['Berita'];

				print($docId . ". (" . $sim . ") <font color=blue><b>" . $judul . "</b></font><br />");
				print($berita . "<hr />");
			} else {
				print("<b>Tidak ada... </b><hr />");
			}
		}
	}
}
