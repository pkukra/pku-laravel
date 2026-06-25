import React, { useState } from "react";
import { Button, Modal, Skeleton } from "antd";

export default function Index({ kode_reg }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading
    const url = `http://10.10.10.10/emr/index.php/igd/cetak_spri/pdf2/RGD011260410-061/415753`

    return (
        <>
            <Button block size="small" onClick={() => setModalOpen(true)} style={{ margin: "2px" }}>
                SPRI
            </Button>

            {/* Modal  */}
            <Modal
                title="Preview  SPRI"
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
                    src={url}
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
