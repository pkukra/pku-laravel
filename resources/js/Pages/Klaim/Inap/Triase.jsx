import React, { useState } from "react";
import { Button, Modal, Skeleton } from "antd";

export default function Index({ kode_reg }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading
    const url = `http://10.10.10.10/emr/index.php/igd/cetak_triase/pdf/RGD011260603-009/0212117`

    return (
        <>
            <Button block size="small" onClick={() => setModalOpen(true)}>
                Triase
            </Button>

            {/* Modal  */}
            <Modal
                title="Preview  Triase"
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
