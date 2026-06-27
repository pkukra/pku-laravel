import React, { useEffect, useState } from "react";
import { PDFDocument } from "pdf-lib";
import { Skeleton, Button, message } from "antd";
import axios from "axios";

export default function Index({ kode_reg = "RBI-26-05-1380", nomer_rm="0108668", no_sep="0153R0030526V014612" }) {
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
                const kodeRegJalan = data?.FDTNO_FAKTUR || data?.kode_reg || data?.kodeRegJalan || Object.values(data || {})[0];

                if (kodeRegJalan) {

                    // SPRI
                    pdfs.push(
                        route("klaim.inap.proxy_pdf", {
                            url: `http://10.10.10.10/emr/index.php/igd/cetak_spri/pdf2/${kodeRegJalan}/${nomer_rm}`,
                        }),
                    );

                    // Triase
                    pdfs.push(
                        route("klaim.inap.proxy_pdf", {
                            url: `http://10.10.10.10/emr/index.php/igd/cetak_triase/pdf/${kodeRegJalan}/${nomer_rm}`,
                        }),
                    );

                    // Laporan Operasi
                    pdfs.push(
                        route("klaim.inap.proxy_pdf", {
                            url: `http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_laporan_operasi/${kode_reg}`,
                        }),
                    );

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

                    // Kwitansi
                    const kwitansiUrl = route("klaim.inap.proxy_pdf", {
                        url: `http://10.10.10.10/emr/index.php/vedika/cetak_billing_ri?faktur_id=${kode_reg}`,
                    });
                    pdfs.push(kwitansiUrl);
                    console.log("🧾 Kwitansi Proxy URL:", kwitansiUrl);

                }
            } catch (e) {
                // Error saat fetch SPRI - lanjut tanpa SPRI/Triase
            }

            // Tambahkan Anastesi (route internal Laravel)
            try {
                pdfs.push(route("klaim.inap.laporan_anastesi_snapshot", { kode_reg }));
            } catch (e) {
                // Error saat tambah Anastesi
            }

            // Tambahkan E-Klaim (route internal Laravel)
            try {
                pdfs.push(route("klaim.inap.cetak_klaim", { no_sep }));
            } catch (e) {
                // Error saat tambah E-Klaim
            }

            const mergedPdf = await PDFDocument.create();

            for (const url of pdfs) {
                try {
                    const response = await fetch(url);

                    if (!response.ok) {
                        continue;
                    }

                    const bytes = await response.arrayBuffer();

                    const pdf = await PDFDocument.load(bytes);

                    const pages = await mergedPdf.copyPages(
                        pdf,
                        pdf.getPageIndices(),
                    );

                    pages.forEach((page) => mergedPdf.addPage(page));
                } catch (err) {
                    // Error merge PDF - lanjut ke PDF berikutnya
                }
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
