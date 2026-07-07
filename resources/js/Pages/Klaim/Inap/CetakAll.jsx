import React, { useEffect, useState } from "react";
import { PDFDocument } from "pdf-lib";
import { Skeleton, message } from "antd";
import axios from "axios";

export default function Index({ kode_reg, nomer_rm, no_sep }) {
    const [loading, setLoading] = useState(true);
    const [pdfUrl, setPdfUrl] = useState(null);

    useEffect(() => {
        init();

        return () => {
            if (pdfUrl) {
                URL.revokeObjectURL(pdfUrl);
            }
        };
    }, []);

    const init = async () => {
        try {
            setLoading(true);

            const [kodeRes, jokRes, partusRes, penunjangRes] =
                await Promise.all([
                    axios.get(
                        route("klaim.inap.get_kode_reg_jalan", {
                            kode_reg_rbi: kode_reg,
                        }),
                    ),
                    axios.get(
                        route("klaim.inap.get_all_jok", {
                            kode_reg,
                        }),
                    ),
                    axios.get(
                        route("klaim.inap.check_is_persalinan", {
                            kode_reg_rbi: kode_reg,
                        }),
                    ),
                    axios.get(
                        route("klaim.inap.penunjang_lain.list", {
                            kode_reg,
                        }),
                    ),
                ]);

            const kodeRekRrj = kodeRes.data?.FRPNOTRANSAKSI ?? null;
            const jokList = jokRes.data || [];
            const isPartus = partusRes.data?.is_partus ?? false;
            const penunjangList = penunjangRes.data || [];
            const urls = buildUrls(
                kodeRekRrj,
                jokList,
                isPartus,
                no_sep,
                penunjangList,
            );

            await mergePdf(urls);
        } catch (error) {
            console.error(error);
            message.error("Gagal mengambil data");
            setLoading(false);
        }
    };

    const buildUrls = (
        kode_rek_rrj,
        jokList,
        isPartus,
        no_sep,
        penunjangList,
    ) => {
        const urls = [];

        // SEP
        urls.push(route("klaim.inap.sep", kode_reg));

        // Resume
        urls.push(
            route("klaim.inap.proxy_pdf", {
                url: `http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_rm/${kode_reg}/2`,
            }),
        );

        // SPRI
        if (kode_rek_rrj) {
            urls.push(
                route("klaim.inap.proxy_pdf", {
                    url: `http://10.10.10.10/emr/index.php/igd/cetak_spri/pdf2/${kode_rek_rrj}/${nomer_rm}`,
                }),
            );

            // Triase
            urls.push(
                route("klaim.inap.proxy_pdf", {
                    url: `http://10.10.10.10/emr/index.php/igd/cetak_triase/pdf/${kode_rek_rrj}/${nomer_rm}`,
                }),
            );
        }

        // Operasi & Anastesi
        if (jokList.length > 0) {
            jokList.forEach((item) => {
                urls.push(
                    route("klaim.inap.proxy_pdf", {
                        url: `http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_laporan_operasi_fjok/${item.FJOKNO_JADWAL}`,
                    }),
                );

                urls.push(
                    route("klaim.inap.laporan_anastesi_snapshot", {
                        kode_reg,
                    }),
                );
            });
        }

        // VK persalinan partus
        if (isPartus) {
            urls.push(
                route("klaim.inap.proxy_pdf", {
                    url: `http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_laporan_persalinan/${kode_reg}`,
                }),
            );
        }

        // penunjang
        urls.push(
            route("klaim.inap.proxy_pdf", {
                url: `http://10.10.10.10/emr/index.php/penunjang/cetak_hasil_penunjang/pdf2/${kode_reg}/${nomer_rm}`,
            }),
        );

        // penunjang lain
        for (const doc of penunjangList.data) {
            const downloadUrl = route("klaim.inap.penunjang_lain.download", {
                kode_reg,
                id: doc.ID,
            });

            urls.push(downloadUrl);
            console.log(`✅ Queued Penunjang Lain: ${doc.ID}`);
        }

        // farmasi
        urls.push(route("klaim.inap.faktur_farmasi", kode_reg));

        // kwitansi
        urls.push(
            route("klaim.inap.proxy_pdf", {
                url: `http://10.10.10.10/emr/index.php/vedika/cetak_billing_ri?faktur_id=${kode_reg}`,
            }),
        );

        // eklaim
        urls.push(route("klaim.inap.cetak_klaim", { no_sep }));

        console.log("URLs to merge:", urls);

        return urls;
    };

    const appendToMergedPdf = async (merged, response) => {
        const contentType = (
            response.headers["content-type"] || ""
        ).toLowerCase();

        // PDF
        if (contentType.includes("application/pdf")) {
            const pdf = await PDFDocument.load(response.data);

            const pages = await merged.copyPages(pdf, pdf.getPageIndices());

            pages.forEach((page) => merged.addPage(page));
            return;
        }

        // JPEG
        if (
            contentType.includes("image/jpeg") ||
            contentType.includes("image/jpg")
        ) {
            const image = await merged.embedJpg(response.data);

            const page = merged.addPage([image.width, image.height]);

            page.drawImage(image, {
                x: 0,
                y: 0,
                width: image.width,
                height: image.height,
            });

            return;
        }

        // PNG
        if (contentType.includes("image/png")) {
            const image = await merged.embedPng(response.data);

            const page = merged.addPage([image.width, image.height]);

            page.drawImage(image, {
                x: 0,
                y: 0,
                width: image.width,
                height: image.height,
            });

            return;
        }

        console.warn("Format tidak didukung:", contentType);
    };

    const mergePdf = async (urls) => {
        try {
            setLoading(true);

            const merged = await PDFDocument.create();

            for (const url of urls) {
                const response = await axios.get(url, {
                    responseType: "arraybuffer",
                });

                await appendToMergedPdf(merged, response);
            }

            const bytes = await merged.save();

            const blob = new Blob([bytes], {
                type: "application/pdf",
            });

            const objectUrl = URL.createObjectURL(blob);

            setPdfUrl((oldUrl) => {
                if (oldUrl) {
                    URL.revokeObjectURL(oldUrl);
                }
                return objectUrl;
            });
        } catch (err) {
            console.error(err);
            message.error("Gagal menggabungkan PDF");
        } finally {
            setLoading(false);
        }
    };

    if (loading) {
        return <Skeleton active />;
    }

    return (
        <iframe
            src={pdfUrl}
            title="Merged PDF"
            style={{
                width: "100%",
                height: "100vh",
                border: "none",
            }}
        />
    );
}
