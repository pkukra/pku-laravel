import React, { useState, useEffect } from "react";
import { Card, Skeleton } from "antd";

export default function Index({ pasien }) {
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading
    const [loadingResume, setLoadingResume] = useState(false);

    useEffect(() => {}, []);

    return (
        <>
            <Card title="Berkas RM pasien IGD">
                {loadingPdf && (
                    <>
                        <Skeleton active />
                    </>
                )}
                {/* PDF Viewer */}
                <iframe
                    src={`http://10.10.10.10/emr/index.php/rm/rawat_jalan/cetak_rm/${pasien?.FRPNOTRANSAKSI}`}
                    width="100%"
                    height="600px"
                    style={{
                        border: "none",
                        display: loadingPdf ? "none" : "block",
                    }}
                    onLoad={() => setLoadingPdf(false)} // Sembunyikan loading saat PDF selesai dimuat
                ></iframe>
            </Card>
        </>
    );
}
