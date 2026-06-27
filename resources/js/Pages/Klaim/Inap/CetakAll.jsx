import React, { useEffect, useState } from "react";
import { PDFDocument } from "pdf-lib";
import { Skeleton, Button, message } from "antd";
import axios from "axios";

export default function Index({ kode_reg = "RBI-26-05-1380", nomer_rm="0108668" }) {
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

                console.log("🔍 FULL RESPONSE:", data);
                console.log("🔑 ALL KEYS:", Object.keys(data || {}));
                console.log("FDTNO_FAKTUR value:", data?.FDTNO_FAKTUR);

                // Cari key yang berisi kode_reg jalan
                const kodeRegJalan = data?.FDTNO_FAKTUR || data?.kode_reg || data?.kodeRegJalan || Object.values(data || {})[0];
                console.log("🎯 kodeRegJalan hasil:", kodeRegJalan);

                if (kodeRegJalan) {
                    console.log("✅ Kode Reg Jalan ditemukan:", kodeRegJalan);

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
                    console.log("✅ SPRI & Triase ditambahkan ke PDFs");
                } else {
                    console.warn("⚠️ FDTNO_FAKTUR tidak ditemukan atau kosong");
                }
                console.log("📋 PDFS setelah tambah SPRI/TRIASE:", pdfs);
            } catch (e) {
                console.error("❌ Error saat fetch SPRI:", e);
            }

            const mergedPdf = await PDFDocument.create();

            for (const url of pdfs) {
                try {
                    console.log("Fetch PDF:", url);

                    const response = await fetch(url);

                    if (!response.ok) {
                        console.error(
                            "Gagal mengambil PDF:",
                            url,
                            response.status,
                        );
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
                    console.error("Gagal merge PDF:", url, err);
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
            console.error(err);
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

    console.log(pdfUrl);

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
