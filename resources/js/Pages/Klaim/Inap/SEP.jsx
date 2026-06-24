import React, { useState } from "react";
import { Button, Modal, Skeleton  } from "antd";

export default function Index({ kode_reg }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true); // Tambahkan state loading  

    return (
        <>
            <Button block size="small" onClick={() => setModalOpen(true)}>
                SEP
            </Button>

            {/* Modal  */}
            <Modal
                title="Preview  SEP"
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
                    src={route("klaim.inap.sep", { kode_reg })}
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
