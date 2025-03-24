import React, { useState, useEffect } from "react";
import { Card, Button, Modal, Skeleton } from "antd";

import PasienInapDetailHasilRadiologi from "./PasienInapDetailHasilRadiologi";

export default function Index({ pasien }) {
    const [hasilLabUrl, setHasilLabUrl] = useState(null);
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading

    const generateLabUrl = async () => {
        try {
            // const response = await axios.get(route("common.lab_url"));
            // setHasilLabUrl(response?.data?.data + pasien.PRWINO_TRANSAKSI);
            setHasilLabUrl(
                `10.10.10.10/emr/index.php/penunjang/lab/hasil_laborat_ranap_lis/${pasien.PRWINO_TRANSAKSI}`
            );
        } catch (error) {
            console.error("Error fetching lab data:", error);
        }
    };

    useEffect(() => {
        generateLabUrl();
    }, []);

    return (
        <>
            <Card title="Hasil Panunjang">
                {/* Button untuk membuka modal */}
                <Button
                    type="primary"
                    onClick={() => setModalOpen(true)}
                    disabled={!hasilLabUrl}
                >
                    Hasil Lab
                </Button>

                <PasienInapDetailHasilRadiologi pasien={pasien} />

                {/* Modal Ant Design */}
                <Modal
                    title="Preview Hasil Lab"
                    open={modalOpen}
                    onCancel={() => setModalOpen(false)}
                    footer={null}
                    width={800}
                >
                    {/* Loading Indicator */}
                    {loadingPdf && (
                        <>
                            <Skeleton active />
                        </>
                    )}

                    {/* PDF Viewer */}
                    <iframe
                        src={`http://10.10.10.10/emr/index.php/penunjang/lab/hasil_laborat_ranap_lis/${pasien?.PRWINO_TRANSAKSI}`}
                        width="100%"
                        height="600px"
                        style={{
                            border: "none",
                            display: loadingPdf ? "none" : "block",
                        }}
                        onLoad={() => setLoadingPdf(false)} // Sembunyikan loading saat PDF selesai dimuat
                    ></iframe>
                </Modal>
            </Card>
        </>
    );
}
