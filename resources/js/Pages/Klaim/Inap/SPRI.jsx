import React, { useState } from "react";
import { Button, Modal, Skeleton, message } from "antd";
import axios from "axios";

export default function Index({ kode_reg, nomer_rm }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(false);
    const [pdfUrl, setPdfUrl] = useState(null);

    const openSPRI = async () => {
        try {
            setLoadingPdf(true);

            const { data } = await axios.get(
                route("klaim.inap.get_kode_reg_jalan", {
                    kode_reg_rbi: kode_reg,
                }),
            );

            if (!data || !data.FDTNO_FAKTUR) {
                message.warning("SPRI tidak ditemukan");
                setLoadingPdf(false);
                return;
            }

            const kodeRegJalan = data.FDTNO_FAKTUR;

            const url = `http://10.10.10.10/emr/index.php/igd/cetak_spri/pdf2/${kodeRegJalan}/${nomer_rm}`;

            setPdfUrl(url);
            setModalOpen(true);
        } catch (error) {
            console.error(error);
            message.error("Gagal mengambil data SPRI");
            setLoadingPdf(false);
        }
    };

    return (
        <>
            <Button
                block
                size="small"
                onClick={openSPRI}
                style={{ margin: "2px" }}
            >
                SPRI
            </Button>

            <Modal
                title="Preview SPRI"
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
