import React, { useState } from "react";
import { Button, Modal, Skeleton, message } from "antd";
import axios from "axios";

export default function Index({ kode_reg, nomer_rm }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(false);
    const [pdfUrl, setPdfUrl] = useState(null);

    const openTriase = async () => {
        try {
            setLoadingPdf(true);

            const { data } = await axios.get(
                route("klaim.inap.get_kode_reg_jalan", {
                    kode_reg_rbi: kode_reg,
                }),
            );

            if (!data || !data.FDTNO_FAKTUR) {
                message.warning("Data Triase tidak ditemukan");
                setLoadingPdf(false);
                return;
            }

            const kodeRegJalan = data.FDTNO_FAKTUR;

            const url = `http://10.10.10.10/emr/index.php/igd/cetak_triase/pdf/${kodeRegJalan}/${nomer_rm}`;

            setPdfUrl(url);
            setModalOpen(true);
        } catch (error) {
            console.error(error);
            message.error("Gagal mengambil data Triase");
            setLoadingPdf(false);
        }
    };

    return (
        <>
            <Button
                block
                size="small"
                onClick={openTriase}
                style={{ margin: "2px" }}
            >
                Triase
            </Button>

            <Modal
                title="Preview Triase"
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
