import React, { useState } from "react";
import { Button, Modal, Skeleton } from "antd";
import axios from "axios";

export default function Index({ kode_reg }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingPdf, setLoadingPdf] = useState(false);
    const [jokList, setJokList] = useState([]);
    const [pdfLoading, setPdfLoading] = useState({});

    const fetchRJOK = async () => {
        setLoadingPdf(true);

        try {
            const response = await axios.get(
                route("klaim.inap.get_all_jok", {
                    kode_reg,
                }),
            );

            const data = response.data || [];

            setJokList(data);

            const initialLoading = {};
            data.forEach((item) => {
                initialLoading[item.FJOKNO_JADWAL] = true;
            });

            setPdfLoading(initialLoading);
        } catch (error) {
            console.error("Error fetching JOK data:", error);
        } finally {
            setLoadingPdf(false);
        }
    };

    return (
        <>
            <Button
                block
                size="small"
                onClick={() => {
                    setModalOpen(true);
                    fetchRJOK();
                }}
                style={{ margin: "2px" }}
            >
                Laporan OK
            </Button>

            <Modal
                title="Preview Laporan Operasi"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                footer={null}
                width={1000}
            >
                {loadingPdf ? (
                    <Skeleton active paragraph={{ rows: 12 }} />
                ) : (
                    jokList.map((item) => (
                        <div
                            key={item.FJOKNO_JADWAL}
                            style={{ marginBottom: 20 }}
                        >
                            {pdfLoading[item.FJOKNO_JADWAL] && (
                                <Skeleton active paragraph={{ rows: 12 }} />
                            )}

                            <iframe
                                src={`http://10.10.10.10/emr/index.php/rm/rawat_inap_no_auth/cetak_laporan_operasi_fjok/${item.FJOKNO_JADWAL}`}
                                width="100%"
                                height="600px"
                                style={{
                                    border: "1px solid #ddd",
                                    display: pdfLoading[item.FJOKNO_JADWAL]
                                        ? "none"
                                        : "block",
                                }}
                                onLoad={() =>
                                    setPdfLoading((prev) => ({
                                        ...prev,
                                        [item.FJOKNO_JADWAL]: false,
                                    }))
                                }
                            />
                        </div>
                    ))
                )}

                {!loadingPdf && jokList.length === 0 && (
                    <div
                        style={{
                            textAlign: "center",
                            padding: "20px",
                        }}
                    >
                        Tidak ada laporan operasi.
                    </div>
                )}
            </Modal>
        </>
    );
}
