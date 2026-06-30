import React, { useEffect, useState } from "react";
import { PDFDocument } from "pdf-lib";
import { Skeleton, Button, message } from "antd";
import axios from "axios";

export default function Index({ kode_reg, nomer_rm, no_sep }) {
    const [pdfUrl, setPdfUrl] = useState(null);
    const [loadingPdf, setLoadingPdf] = useState(true);

    useEffect(() => {
        mergePdfs();
    }, []);

    const mergePdfs = async () => {
        try {
            setLoadingPdf(true);

            const pdfs = [
                // SEP Laravel
                route("klaim.inap.sep", kode_reg),

                // RM CI3
                route("klaim.inap.proxy_pdf", {
                    url: `http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_rm/${kode_reg}/2`,
                }),
            ];

            // Ambil SPRI jika ada
            try {
                const { data } = await axios.get(
                    route("klaim.inap.get_kode_reg_jalan", {
                        kode_reg_rbi: kode_reg,
                    }),
                );

                // Cari key yang berisi kode_reg jalan
                const kodeRegJalan =
                    data?.FDTNO_FAKTUR ||
                    data?.kode_reg ||
                    data?.kodeRegJalan ||
                    Object.values(data || {})[0];

                if (kodeRegJalan) {
                    // SPRI
                    pdfs.push(
                        route("klaim.inap.proxy_pdf", {
                            url: `http://10.10.10.10/emr/index.php/igd/cetak_spri/pdf2/${kode_rek_rrj}/${nomer_rm}`,
                        }),
                    );

                    // Triase
                    pdfs.push(
                        route("klaim.inap.proxy_pdf", {
                            url: `http://10.10.10.10/emr/index.php/igd/cetak_triase/pdf/${kodeRegJalan}/${nomer_rm}`,
                        }),
                    );

                    // Laporan Operasi (hanya jika ada FJOK)
                    try {
                        const { data: jokData } = await axios.get(
                            route("klaim.inap.get_all_jok", {
                                kode_reg,
                            }),
                        );

                        if (jokData && jokData.length > 0) {
                            jokData.forEach((jok) => {
                                pdfs.push(
                                    route("klaim.inap.proxy_pdf", {
                                        url: `http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_laporan_operasi_fjok/${jok.FJOKNO_JADWAL}`,
                                    }),
                                );
                            });
                            console.log(
                                `✅ Added ${jokData.length} Laporan Operasi`,
                            );
                        }
                    } catch (jokErr) {
                        // Tidak ada JOK - skip Laporan Operasi
                    }

                    // Tambahkan Anastesi (route internal Laravel)
                    try {
                        pdfs.push(
                            route("klaim.inap.laporan_anastesi_snapshot", {
                                kode_reg,
                            }),
                        );
                    } catch (e) {
                        // Error saat tambah Anastesi
                    }

                    // Laporan Persalinan (hanya jika persalinan)
                    try {
                        const { data: partusData } = await axios.get(
                            route("klaim.inap.check_is_persalinan", {
                                kode_reg_rbi: kode_reg,
                            }),
                        );

                        if (partusData?.is_partus) {
                            pdfs.push(
                                route("klaim.inap.proxy_pdf", {
                                    url: `http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_laporan_persalinan/${kode_reg}`,
                                }),
                            );
                        }
                    } catch (partusErr) {
                        // Error check persalinan - lanjut tanpa laporan persalinan
                    }

                    // Penunjang
                    pdfs.push(
                        route("klaim.inap.proxy_pdf", {
                            url: `http://10.10.10.10/emr/index.php/penunjang/cetak_hasil_penunjang/pdf2/${kode_reg}/${nomer_rm}`,
                        }),
                    );
                }
            } catch (e) {
                // Error saat fetch SPRI - lanjut tanpa SPRI/Triase
            }

            // Penunjang Lain (hasil upload) - process sebelum merge
            try {
                const { data: penunjangLainData } = await axios.get(
                    route("klaim.inap.penunjang_lain.list", {
                        kode_reg,
                    }),
                );

                if (
                    penunjangLainData?.data &&
                    penunjangLainData.data.length > 0
                ) {
                    console.log(
                        `📋 Found ${penunjangLainData.data.length} Penunjang Lain`,
                    );

                    for (const doc of penunjangLainData.data) {
                        const downloadUrl = route(
                            "klaim.inap.penunjang_lain.download",
                            {
                                kode_reg,
                                id: doc.ID,
                            },
                        );

                        // Jika PDF, tambahkan ke pdfs array
                        // Jika gambar, akan diproses langsung saat merge
                        pdfs.push(downloadUrl);
                        console.log(`✅ Queued Penunjang Lain: ${doc.ID}`);
                    }
                } else {
                    console.log(`ℹ️ Tidak ada Penunjang Lain`);
                }
            } catch (penunjangLainErr) {
                console.log(
                    `ℹ️ Error fetching Penunjang Lain:`,
                    penunjangLainErr.message,
                );
            }

            const mergedPdf = await PDFDocument.create();

            // Process semua PDF dari pdfs array
            for (const url of pdfs) {
                try {
                    console.log("📄 Fetching:", url);

                    const response = await fetch(url);

                    if (!response.ok) {
                        console.error(
                            `❌ Failed - Status ${response.status}:`,
                            url,
                        );
                        continue;
                    }

                    const bytes = await response.arrayBuffer();
                    console.log(`✅ Success - Size: ${bytes.byteLength} bytes`);

                    const contentType = response.headers.get("content-type");

                    // Jika format gambar, convert ke PDF
                    if (
                        contentType?.includes("image/") ||
                        contentType?.includes("jpeg") ||
                        contentType?.includes("png")
                    ) {
                        console.log(`🖼️ Converting image to PDF`);

                        const imagePdf = await PDFDocument.create();
                        const img = await imagePdf
                            .embedPng(bytes)
                            .catch(async () => await imagePdf.embedJpg(bytes));

                        const page = imagePdf.addPage([img.width, img.height]);
                        page.drawImage(img, {
                            x: 0,
                            y: 0,
                            width: img.width,
                            height: img.height,
                        });

                        const pages = await mergedPdf.copyPages(
                            imagePdf,
                            imagePdf.getPageIndices(),
                        );
                        pages.forEach((p) => mergedPdf.addPage(p));
                        console.log(`✅ Image converted & added`);
                    } else {
                        // PDF format
                        const pdf = await PDFDocument.load(bytes);
                        const pages = await mergedPdf.copyPages(
                            pdf,
                            pdf.getPageIndices(),
                        );
                        pages.forEach((page) => mergedPdf.addPage(page));
                        console.log(`✅ Added ${pages.length} pages`);
                    }
                } catch (err) {
                    console.error("❌ Error merge PDF:", url, err.message);
                }
            }

            // Nota Farmasi
            try {
                const FarmasiUrl = route("klaim.inap.faktur_farmasi", { kode_reg });
                const response = await fetch(FarmasiUrl);

                if (response.ok) {
                    const bytesfarmasi = await response.arrayBuffer();
                    const pdfFarmasi = await PDFDocument.load(bytesfarmasi);
                    const pagesFarmasi = await mergedPdf.copyPages(
                        pdfFarmasi,
                        pdfFarmasi.getPageIndices(),
                    );
                    pagesFarmasi.forEach((page) => mergedPdf.addPage(page));
                    console.log(`✅ Farmasi added`);
                } else {
                    console.warn(`⚠️ Cannot fetch Farmasi`);
                }
            } catch (e) {
                console.warn(`⚠️ Error saat tambah Farmasi:`, e.message);
            }

            if (mergedPdf.getPageCount() === 0) {
                message.error("Tidak ada PDF yang berhasil digabung");
                return;
            }

            // Tambahkan Kwitansi (sebelum E-Klaim)
            try {
                const kwitansiUrl = route("klaim.inap.proxy_pdf", {
                    url: `http://10.10.10.10/emr/index.php/vedika/cetak_billing_ri?faktur_id=${kode_reg}`,
                });
                console.log("🧾 Kwitansi Proxy URL:", kwitansiUrl);

                const response = await fetch(kwitansiUrl);

                if (response.ok) {
                    const bytes = await response.arrayBuffer();
                    const contentType = response.headers.get("content-type");

                    if (
                        contentType?.includes("image/") ||
                        contentType?.includes("jpeg") ||
                        contentType?.includes("png")
                    ) {
                        const imagePdf = await PDFDocument.create();
                        const img = await imagePdf
                            .embedPng(bytes)
                            .catch(async () => await imagePdf.embedJpg(bytes));

                        const page = imagePdf.addPage([img.width, img.height]);
                        page.drawImage(img, {
                            x: 0,
                            y: 0,
                            width: img.width,
                            height: img.height,
                        });

                        const pages = await mergedPdf.copyPages(
                            imagePdf,
                            imagePdf.getPageIndices(),
                        );
                        pages.forEach((p) => mergedPdf.addPage(p));
                    } else {
                        const pdf = await PDFDocument.load(bytes);
                        const pages = await mergedPdf.copyPages(
                            pdf,
                            pdf.getPageIndices(),
                        );
                        pages.forEach((page) => mergedPdf.addPage(page));
                    }

                    console.log(`✅ Kwitansi added`);
                } else {
                    console.warn(`⚠️ Cannot fetch Kwitansi`);
                }
            } catch (e) {
                console.warn(`⚠️ Error saat tambah Kwitansi:`, e.message);
            }

            // Tambahkan E-Klaim (route internal Laravel)
            try {
                const ekClaimUrl = route("klaim.inap.cetak_klaim", { no_sep });
                const response = await fetch(ekClaimUrl);

                if (response.ok) {
                    const bytes = await response.arrayBuffer();
                    const pdf = await PDFDocument.load(bytes);
                    const pages = await mergedPdf.copyPages(
                        pdf,
                        pdf.getPageIndices(),
                    );
                    pages.forEach((page) => mergedPdf.addPage(page));
                    console.log(`✅ E-Klaim added`);
                } else {
                    console.warn(`⚠️ Cannot fetch E-Klaim`);
                }
            } catch (e) {
                console.warn(`⚠️ Error saat tambah E-Klaim:`, e.message);
            }

            if (mergedPdf.getPageCount() === 0) {
                message.error("Tidak ada PDF yang berhasil digabung");
                return;
            }

            const mergedBytes = await mergedPdf.save();

            const blob = new Blob([mergedBytes], {
                type: "application/pdf",
            });

            const objectUrl = URL.createObjectURL(blob);

            setPdfUrl(objectUrl);
        } catch (err) {
            message.error("Gagal membuat PDF gabungan");
        } finally {
            setLoadingPdf(false);
        }
    };

    const downloadMergedPdf = () => {
        if (!pdfUrl) return;

        const a = document.createElement("a");
        a.href = pdfUrl;
        a.download = `klaim-${kode_reg}.pdf`;
        a.click();
    };

    return (
        <>
            {loadingPdf && <Skeleton active paragraph={{ rows: 10 }} />}

            {!loadingPdf && pdfUrl && (
                <>
                    <Button
                        type="primary"
                        onClick={downloadMergedPdf}
                        style={{ marginBottom: 10 }}
                    >
                        Download PDF Gabungan
                    </Button>

                    <iframe
                        src={pdfUrl}
                        width="100%"
                        height="800px"
                        style={{ border: "none" }}
                    />
                </>
            )}
        </>
    );
}
