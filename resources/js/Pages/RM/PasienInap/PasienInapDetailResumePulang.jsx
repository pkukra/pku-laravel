import React, { useState } from "react";
import { Modal, Button, Skeleton  } from "antd";

export default function Index({ pasien }) {
    const [modalResumeOpen, setModalResumeOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading

    return (
        <>
            {/* Button untuk membuka modal Resume Pulang*/}
            <Button
                type="primary"
                onClick={() => setModalResumeOpen(true)}
                style={{ marginRight: 2 }}
            >
                Resume
            </Button>

            {/* Modal Resume */}
            <Modal
                title="Preview Resume Pulang"
                open={modalResumeOpen}
                onCancel={() => setModalResumeOpen(false)}
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
                    src={`http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_rm/${pasien?.PRWINO_TRANSAKSI}/2`}
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
