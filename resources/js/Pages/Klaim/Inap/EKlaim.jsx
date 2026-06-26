import React, { useState } from "react";
import { Button, Modal, Skeleton } from "antd";

export default function Index({ no_sep }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading

    return (
        <>
            <Button block size="small" onClick={() => setModalOpen(true)} style={{ margin: "2px" }}>
                EKlaim
            </Button>

            {/* Modal  */}
            <Modal
                title="Preview  Berkas EKlaim"
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
                    src={route("klaim.inap.cetak_klaim", { no_sep })}
                    width="100%"
                    height="600px"
                    style={{
                        border: "none",
                        display: loadingPdf ? "none" : "block",
                    }}
                    onLoad={() => setLoadingPdf(false)}
                ></iframe>
            </Modal>
        </>
    );
}
