import React, { useState } from "react";
import { Button, Modal, Skeleton } from "antd";

export default function Index({ kode_reg, nomer_rm }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading

    return (
        <>
            <Button
                block
                size="small"
                onClick={() => setModalOpen(true)}
                style={{ margin: "2px" }}
            >
                Penunjang
            </Button>

            {/* Modal  */}
            <Modal
                title="Preview  Hasil Penunjang"
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
                    src={`http://10.10.10.10/emr/index.php/penunjang/cetak_hasil_penunjang/pdf2/${kode_reg}/${nomer_rm}`}
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
