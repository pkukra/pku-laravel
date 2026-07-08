import React, { useState } from "react";
import { Modal, Button, Tag } from "antd";
import moment from "moment";
import axios from "axios";
moment.locale("id");

export default function LaporanOK({ pasien }) {
    const [loading, setLoading] = useState(false);
    const [dataOK, setDataOK] = useState([]);
    const [modalOpen, setModalOpen] = useState(false);

    const fetchLaporanOK = async () => {
        setLoading(true);
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.get_laporan_ok", {
                    nomer_rm: pasien.FRPPASIEN_ID,
                }),
            );
            setDataOK(response?.data?.data || []);
        } catch (error) {
            console.error("Error fetching laporan OK:", error);
        } finally {
            setLoading(false);
        }
    };

    return (
        <>
            <Button
                style={{ margin: 2 }}
                type="primary"
                onClick={() => {
                    setModalOpen(true);
                    fetchLaporanOK();
                }}
            >
                History Laporan OK
            </Button>

            <Modal
                destroyOnClose
                title="History Laporan OK"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                footer={null}
                width={800}
            >
                <div
                    style={{
                        maxHeight: "70vh",
                        overflowY: "auto",
                        lineHeight: 1.6,
                    }}
                >
                    {loading ? (
                        <p>Loading...</p>
                    ) : dataOK.length === 0 ? (
                        <p>Data laporan tidak tersedia.</p>
                    ) : (
                        dataOK.map((laporan, index) => {
                            const showDivider =
                                dataOK.length > 1 && index < dataOK.length - 1;

                            return (
                                <div
                                    key={index}
                                    style={{
                                        marginBottom: 24,
                                        paddingBottom: showDivider ? 12 : 0,
                                        borderBottom: showDivider
                                            ? "1px solid #eee"
                                            : "none",
                                    }}
                                >
                                    <p>
                                        <strong>Operasi {index + 1}</strong>
                                    </p>
                                    <p>
                                        <strong>Tanggal:</strong>{" "}
                                        {laporan.mdd_date
                                            ? moment(laporan.mdd_date).format(
                                                  "DD MMMM YYYY",
                                              )
                                            : "-"}{" "}
                                        {laporan.mdd_time || "-"}
                                    </p>

                                    <p>
                                        <strong>Jaringan / Insisi:</strong>{" "}
                                        {laporan.FS_JARINGAN || "-"}
                                    </p>
                                    <p>
                                        <strong>Diagnosa Pre Operasi:</strong>{" "}
                                        {laporan.FS_DIAGNOSIS || "-"}
                                    </p>
                                    <p>
                                        <strong>Nama / Macam Operasi:</strong>{" "}
                                        {laporan.FS_TINDAKAN_OP || "-"}
                                    </p>
                                    <p>
                                        <strong>Diagnosa Post Operasi:</strong>{" "}
                                        {laporan.FD_DIAG_PASCA_BEDAH || "-"}
                                    </p>
                                    <p>
                                        <strong>Pemeriksaan PA:</strong>{" "}
                                        {laporan.FS_PA || "-"}
                                    </p>
                                    <p>
                                        <strong>Komplikasi:</strong>{" "}
                                        {laporan.FS_KOMPLIKASI || "-"}
                                    </p>
                                    <p>
                                        <strong>Tindakan Pembedahan:</strong>{" "}
                                        {laporan.FS_TINDAKAN_OP || "-"}
                                    </p>

                                    <div>
                                        <strong>Laporan Operasi:</strong>
                                        <div
                                            style={{ marginTop: 4 }}
                                            dangerouslySetInnerHTML={{
                                                __html: (
                                                    laporan.FS_CATATAN || "-"
                                                ).replace(/\r\n|\n/g, "<br/>"),
                                            }}
                                        />
                                    </div>
                                </div>
                            );
                        })
                    )}
                </div>
            </Modal>
        </>
    );
}
