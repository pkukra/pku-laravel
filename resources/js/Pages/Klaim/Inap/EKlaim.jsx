import React, { useState } from "react";
import { Button, Modal, Skeleton } from "antd";

export default function Index({ no_sep }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(true);

    const handleOpen = () => {
        if (!no_sep) return;

        setLoadingPdf(true);
        setModalOpen(true);
    };

    const pdfUrl = no_sep ? route("klaim.inap.cetak_klaim", { no_sep }) : null;

    return (
        <>
            <Button
                block
                size="small"
                style={{ margin: "2px" }}
                disabled={!no_sep}
                onClick={handleOpen}
            >
                {no_sep ? "Eklaim" : "Invalid SEP"}
            </Button>

            <Modal
                title="Preview Berkas EKlaim"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                footer={null}
                width={800}
                destroyOnClose
            >
                {loadingPdf && <Skeleton active />}

                {pdfUrl && (
                    <iframe
                        src={pdfUrl}
                        width="100%"
                        height="600px"
                        style={{
                            border: "none",
                            display: loadingPdf ? "none" : "block",
                        }}
                        onLoad={() => setLoadingPdf(false)}
                    />
                )}
            </Modal>
        </>
    );
}
