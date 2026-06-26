<style type="text/Css">
    table.page_header {
    width: 100%;
}
table.page_header img {
    float: left;
}
table.content {
    width: 100%;
    margin : 0px;
}
.content {
    font-size: 10px;
    text-align:'left';
}
.content td {
    padding: 1px;
}

table.fjpp {
    font-size: 10px;
    width: 100%;
    border-collapse: collapse;
}
.fjpp th, .fjpp td {
    border: 1px solid black;
}
.fjpp td {
    padding: 5px;
}

</style>

<page orientation="P" backtop="5mm" backleft="5mm">
    <!-- <page_header> -->
    <table class="page_header">
        <tr>
            <td style="width:33%;">
                <img src="<?php base_url() ?>resource/doc/images/icon/logo-bpjs.png" width="200" height="35" />
            </td>
            <td style="width:33%;text-align: center; font-size: 12px;">
                SURAT ELEGIBILITAS PESERTA <br> <br>
                RS PKU Muhammadiyah Karanganyar
            </td>
            <td style="width:33%;text-align: left; font-size: 14px;">
                <p><strong>,<?= $sep->FMPRB ?></strong></p>
            </td>
        </tr>
    </table>
    <!-- </page_header> -->
    <br>
    <table class="content">
        <tr>
            <td style="width:19%">
                No. SEP
            </td>
            <td style="width:1%">:</td>
            <td style="width:40%">
                <strong style="font-size:15px"><?= $sep->FMNOSEP ?></strong>
            </td>
            <td style="width:10%">
            </td>
            <td style="width:1%"></td>
            <td style="width:25%">
            </td>
        </tr>

        <tr>
            <td>
                Tgl. SEP
            </td>
            <td>:</td>
            <td>
                <?= $sep->FMTGL_SEP_FORMATTED ?>
            </td>
            <td>
                Peserta
            </td>
            <td>:</td>
            <td>
                <strong style="font-size:11px"><?= $pasien->FMPESERTA ?></strong>
            </td>
        </tr>

        <tr>
            <td>
                No. Kartu
            </td>
            <td>:</td>
            <td>
                <?= $sep->FMNO_KARTU ?> <span style="margin-left:70px">( MR. : <strong style="font-size:13px"><?= $sep->FMPASIEN_ID ?></strong>)</span>
            </td>
            <td>
                C O B
            </td>
            <td>:</td>
            <td>

            </td>
        </tr>

        <tr>
            <td>
                Nama Peserta
            </td>
            <td>:</td>
            <td>
                <?= $sep->FMNAMA_PESERTA ?>
            </td>
            <td>
                Jns Rawat
            </td>
            <td>:</td>
            <td>
                <?= ($sep->FMJENISRAWAT == 2) ? "Rawat jalan" : "Rawat Inap" ?>
            </td>
        </tr>

        <tr>
            <td>
                Tgl. Lahir
            </td>
            <td>:</td>
            <td>
                <?= $sep->FMTGL_LAHIR_FORMATTED ?><span style="margin-left:50px">( Kelamin : <?= ($sep->FMJENIS_KELAMIN == "L") ? "Laki-Laki" : "Perempuan" ?> )</span>
            </td>
            <td>
                Jns Kunjung
            </td>
            <td>:</td>
            <td>
                <?= $sep->TUJ_KUNJUNGAN ?>
            </td>
        </tr>

        <tr>
            <td>
                No. Telepon
            </td>
            <td>:</td>
            <td>
                <?= ($sep->telp) ? $sep->telp : $sep->FMNOTELP ?>
            </td>
            <td>

            </td>
            <td></td>
            <td>
            </td>
        </tr>

        <tr>
            <td>
                Sub/Spesialis
            </td>
            <td>:</td>
            <td>
                <?= $sep->FMPOLYN ?>
            </td>
            <td>
                Poli Perujuk
            </td>
            <td>:</td>
            <td>
            </td>
        </tr>

        <tr>
            <td>
                Dokter
            </td>
            <td>:</td>
            <td>
                <?= $sep->dpjpn ?>
            </td>
            <td>
                Hak Rawat
            </td>
            <td>:</td>
            <td>
                <strong style="font-size:13px"><?= $pasien->FMNAMA_KELAS ?></strong>
            </td>
        </tr>

        <tr>
            <td>
                Faskes Perujuk
            </td>
            <td>:</td>
            <td>
                <?= $sep->FMPPK_RUJUKANN ?>
            </td>
            <td>
                Kls Rawat
            </td>
            <td>:</td>
            <td>
                -
            </td>
        </tr>

        <tr>
            <td>
                Diagnosa Awal
            </td>
            <td>:</td>
            <td>
                <?= $sep->DIAGNOSA_AWAL ?>
            </td>
            <td>
                Penjamin
            </td>
            <td>:</td>
            <td>
                -
            </td>
        </tr>

        <tr>
            <td>
                Catatan
            </td>
            <td>:</td>
            <td>
                <?= $sep->FMCATATAN ?>
            </td>
            <td>
            </td>
            <td></td>
            <td>
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <small>
                    *Saya Menyetujui BPJS menggunakan informasi Medis Pasien jika diperlukan<br>
                    &nbsp;*SEP bukan sebagai bukti penjaminan peserta
                </small>
            </td>
            <td></td>
            <td></td>
            <td style="text-align:center;">Pasien / Keluarga Pasien <br> <br>
                <qrcode value="<?= $sep->FMNO_KARTU ?>" style="width: 15mm; background-color: white; color: black;"></qrcode> <br>
                __________________
            </td>
        </tr>
        <tr>
            <td colspan="3">Cetakan ke &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?= $sep->FMPCETAK ?> &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; <?= $sep->JAM_TANGGAL_SEP ?></td>
            <td></td>
            <td></td>
            <td style="text-align:center;">
                <?= $sep->FMNAMA_PESERTA ?>
            </td>
        </tr>
    </table>

    <table class="fjpp">
        <tr>
            <td style="text-align:center;" colspan="4">
                <strong>FORMULIR JAMINAN PELAYANAN PASIEN JAMINAN KESEHATAN NASIONAL</strong>
            </td>
        </tr>

        <tr>
            <td style="text-align:center;" colspan="2">
                <b>DIAGNOSIS</b>
            </td>
            <td style="text-align:center;">
                CODE ICD X
            </td>
        </tr>
        <?php if (count($penyakit_premiers) > 0): ?>
            <?php $i = 1;
            foreach ($penyakit_premiers as $key => $penyakit_premier) { ?>
                <tr>
                    <td style="text-align:left; width:20%; vertical-align:top;">
                        <b><?= ($i == 1) ? "Diagnosa Primer" : ""; ?></b>
                    </td>
                    <td style="text-align:left; width:60%;">
                        <?= $penyakit_premier["PENYAKIT"] ?>
                    </td>
                    <td style="text-align:center; width:20%;">
                        <?= $penyakit_premier["MRPKD_PENYAKIT"] ?>
                    </td>
                </tr>
            <?php $i++;
            } ?>
        <?php else: ?>
            <tr>
                <td style="text-align:left; width:20%; vertical-align:top;">
                    <b>Diagnosa Primer</b>
                </td>
                <td style="text-align:left; width:60%;">

                </td>
                <td style="text-align:center; width:20%;">
                </td>
            </tr>
        <?php endif; ?>

        <?php if (count($penyakit_sekunders) > 0): ?>
            <?php $i = 1;
            foreach ($penyakit_sekunders as $key => $penyakit_sekunder) { ?>
                <tr>
                    <td style="text-align:left; width:20%; vertical-align:top;">
                        <b><?= ($i == 1) ? "Diagnosa Sekunder" : ""; ?> </b>
                    </td>
                    <td style="text-align:left; width:60%;">
                        <?= $penyakit_sekunder["PENYAKIT"] ?>
                    </td>
                    <td style="text-align:center; width:20%;">
                        <?= $penyakit_sekunder["MRPKD_PENYAKIT"] ?>
                    </td>
                </tr>
            <?php $i++;
            } ?>
        <?php else: ?>
            <tr>
                <td style="text-align:left; width:20%; vertical-align:top;">
                    <b>Diagnosa Sekunder</b>
                </td>
                <td style="text-align:left; width:60%;">

                </td>
                <td style="text-align:center; width:20%;">
                </td>
            </tr>
        <?php endif; ?>
    </table>
    <br>
    <table class="fjpp">
        <tr>
            <td style="text-align:center;" colspan="4">
                <strong>TINDAKAN YANG DI LAKUKAN</strong>
            </td>
        </tr>
        <tr>
            <td style="width:55%; text-align:center;">
                <b>TINDAKAN PRIMER</b>
            </td>
            <td style="width:15%; text-align:center;">
                <b>CODE ICD 9 CM</b>
            </td>
            <td style="width:15%; text-align:center;">
                <b>Paraf Petugas</b>
            </td>
            <td style="width:15%; text-align:center;">
                <b>Paraf Pasien/Klg</b>
            </td>
        </tr>
        <?php foreach ($tindakans as $key => $tindakan): ?>
            <tr style="vertical-align:top;">
                <td style="text-align:left; width:55%; vertical-align:top;">
                    <div style="white-space: normal; word-break: break-word;">
                        <?= $tindakan['FMI9KETERANGAN'] ?> 
                    </div>
                </td>
                <td style="text-align:center;"><?= $tindakan['MRTKD_TINDAKAN'] ?> <br> <br></td>
                <td style="text-align:left;"></td>
                <td style="text-align:left;"></td>
            </tr>
        <?php endforeach; ?>
        <tr>
            <td style="width:100%; text-align:left;" colspan="4">
                <strong>Pemeriksaan penunjang yang mendukung diagnosis :</strong>
            </td>
        </tr>

        <tr>
            <td style="text-align:center;" colspan="2" rowspan="2">
            </td>
            <td style="text-align:center;">
                <b>Paraf Petugas</b>
            </td>
            <td style="text-align:center;">
                <b>Paraf Pasien/Klg</b>
            </td>
        </tr>
        <tr>
            <td style="text-align:center;"><br><br><br><br><br><br></td>
            <td style="text-align:center;"></td>
        </tr>

        <tr>
            <td style="width:100%; text-align:left;" colspan="4">
                <strong>Tanggal :</strong>
            </td>
        </tr>

        <tr>
            <td style="text-align:left; vertical-align:top;" colspan="2">
                <table style="border-collapse: collapse; border: none;">
                    <tr>
                        <td style="border: none;"><b>Tindak Lanjut :</b></td>
                    </tr>
                    <tr>
                        <td style="border: none; width:150px">
                            Rawat Jalan <br><br>
                            Pulang Paksa <br><br>
                            Dirujuk Ke ...............
                        </td>
                        <td style="border: none;">
                            Rawat Inap <br><br>
                            Meninggal <br><br>
                            Konsul Ke ...............
                        </td>
                    </tr>
                </table>
            </td>
            <td style="text-align:center;" colspan="2">
                Dokter <br>
                <qrcode value="<?= $sep->dpjpn ?>, <?= $sep->JAM_TANGGAL_SEP ?>" style="width: 15mm; background-color: white; color: black;"></qrcode> <br>
                __________________ <br><br>
                <?= $sep->dpjpn ?>
            </td>
        </tr>
        <tr>
            <td style="width:100%; text-align:left;" colspan="4">
                <strong>Catatan Khusus :
                    <?php if (count($catatans) < 1): ?>
                        <br><br><br><br><br><br>
                    <?php endif; ?>
                </strong>
                <?php foreach ($catatans as $key => $catatan): ?>
                    <p><?= ($catatan['MRCATATANKHUSUS']) ?></p>
                <?php endforeach; ?>
            </td>
        </tr>
    </table>

</page>