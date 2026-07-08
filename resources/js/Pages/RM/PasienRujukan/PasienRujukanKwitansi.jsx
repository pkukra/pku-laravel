import React, { useState } from "react";
import { Modal, Button, Skeleton } from "antd";

export default function Index({ pasien }) {
    const [modalKwitansiOpen, setModalKwitansiOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading

    return (
        <>
            {/* Button untuk membuka modal */}
            <Button
                type="primary"
                style={{ margin: 2 }}
                onClick={() => {
                    setModalKwitansiOpen(true);
                    return;
                }}
            >
                Kwitansi Rajal
            </Button>

            {/* Modal  */}
            <Modal
                title="Preview Kwitansi Rajal"
                open={modalKwitansiOpen}
                onCancel={() => setModalKwitansiOpen(false)}
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
                    src={`http://10.10.10.10/emr/index.php/vedika/cetak_billing/index/${pasien.FRPNOTRANSAKSI}`}
                    width="100%"
                    height="600px"
                    style={{
                        border: "none",
                        display: loadingPdf ? "none" : "block",
                    }}
                    onLoad={() => setLoadingPdf(false)} // Sembunyikan loading saat PDF selesai dimuat
                ></iframe>
            </Modal>
        </>
    );
}
