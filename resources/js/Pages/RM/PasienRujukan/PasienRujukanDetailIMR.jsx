import React, { useState } from "react";
import { Modal, Button, Skeleton  } from "antd";

export default function Index({ dataTransaksi }) {
    const [modalIMROpen, setModalIMROpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading

    return (
        <>
            {/* Button untuk membuka modal IMR*/}
            <Button
                type="primary"
                style={{ margin: 2 }}
                onClick={() => {
                    setModalIMROpen(true)
                    return;
                }}
            >
                IMR
            </Button>

            {/* Modal IMR */}
            <Modal
                title="Preview IMR"
                open={modalIMROpen}
                onCancel={() => setModalIMROpen(false)}
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
                    src={`http://10.10.10.10/emr/index.php/rm/rawat_jalan_no_auth/irm/${dataTransaksi?.FRPNOTRANSAKSI}/${dataTransaksi?.FRPPASIEN_ID}`}
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
