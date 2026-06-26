import React, { useState } from "react";
import { Button, Modal, Skeleton, message } from "antd";
import axios from "axios";

export default function Index({ kode_reg }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(false);
    const [pdfUrl, setPdfUrl] = useState(null);

    const openVK = async () => {
        try {
            setLoadingPdf(true);

            const { data } = await axios.get(
                route("klaim.inap.check_is_persalinan", {
                    kode_reg_rbi: kode_reg,
                }),
            );

            if (!data?.is_partus) {
                message.warning("Pasien bukan kasus persalinan");
                setLoadingPdf(false);
                return;
            }

            setPdfUrl(
                `http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_laporan_persalinan/${kode_reg}`,
            );

            setModalOpen(true);
        } catch (error) {
            console.error(error);
            message.error("Gagal memeriksa data persalinan");
            setLoadingPdf(false);
        }
    };

    return (
        <>
            <Button
                block
                size="small"
                onClick={openVK}
                style={{ margin: "2px" }}
            >
                VK
            </Button>

            <Modal
                title="Preview VK"
                open={modalOpen}
                onCancel={() => {
                    setModalOpen(false);
                    setPdfUrl(null);
                    setLoadingPdf(false);
                }}
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
