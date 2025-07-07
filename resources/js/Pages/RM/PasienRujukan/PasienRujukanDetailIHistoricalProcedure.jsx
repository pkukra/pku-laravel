import React, { useState, useEffect } from "react";
import { Modal, Button } from "antd";
import moment from "moment";

export default function Index({ pasien }) {
    const [modalProceduresHistoryOpen, setModalProceduresHistoryOpen] =
        useState(false);
    const [procedures, setProcedures] = useState([]);

    const fetchProceduresHistory = () => {
        axios
            .get(
                route("rm.procedures_history", {
                    pasien_id: pasien?.FRPPASIEN_ID,
                })
            )
            .then((response) => {
                setProcedures(response?.data || []);
            })
            .catch((error) =>
                console.error("Error fetchProceduresHistory:", error)
            )
            .finally(() => {
                return;
            });
    };

    return (
        <>
            <Button
                type="primary"
                style={{ margin: 2 }}
                onClick={() => {
                    setModalProceduresHistoryOpen(true);
                    fetchProceduresHistory();
                    return;
                }}
            >
                Procedures History
            </Button>

            <Modal
                title="Preview Procedures History"
                open={modalProceduresHistoryOpen}
                onCancel={() => setModalProceduresHistoryOpen(false)}
                footer={null}
                width={800}
            >
                <div style={{ maxHeight: "70vh", overflowY: "auto" }}>
                    <p>iDRG:</p>
                    {procedures?.idrg?.length > 0 ? (
                        <ul>
                            {procedures?.idrg?.map((procedure, index) => (
                                <li key={index}>
                                    {moment(procedure?.created_at).format(
                                        "DD-MMM-YYYY"
                                    )}{" "}
                                    - {procedure?.code} -{" "}
                                    {procedure?.description}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p>Tidak ada data prosedur yang ditemukan.</p>
                    )}
                    <p>INACBG:</p>
                    {procedures?.inacbg?.length > 0 ? (
                        <ul>
                            {procedures?.inacbg?.map((procedure, index) => (
                                <li key={index}>
                                    {moment(procedure?.created_at).format(
                                        "DD-MMM-YYYY"
                                    )}{" "}
                                    - {procedure?.code} -{" "}
                                    {procedure?.description}
                                </li>
                            ))}
                        </ul>
                    ) : (
                        <p>Tidak ada data prosedur yang ditemukan.</p>
                    )}
                </div>
            </Modal>
        </>
    );
}
