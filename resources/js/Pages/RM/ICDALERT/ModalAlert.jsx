import React, { useState, useEffect } from "react";
import { Modal, Button } from "antd";

const ModalAlert = ({ code }) => {
    const [loadingAlert, setLoadingAlert] = useState(false);
    const [modalAlertOpen, setModalAlertOpen] = useState(false);
    const [alertData, setAlertData] = useState([]);

    const fetchDataAlert = async () => {
        setLoadingAlert(true);
        setModalAlertOpen(true);
        axios
            .get(route("rm.icd.list_alert", { code: code }))
            .then((response) => {
                setAlertData(response?.data || []);
            })
            .catch((error) =>
                console.error("Error fetching data pasien:", error)
            )
            .finally(() => {
                setLoadingAlert(false);
            });
    };

    return (
        <>
            <Button
                onClick={() => {
                    fetchDataAlert();
                }}
            >
                Tampilakn {code}
            </Button>
            <Modal
                destroyOnClose
                title="Tambahkan Alert Kode ICD"
                open={modalAlertOpen}
                onCancel={() => {
                    setModalAlertOpen(false);
                    setAlertData([]);
                }}
                footer={null}
                width={1000}
                loading={loadingAlert}
            >
                <table
                    style={{
                        width: "100%",
                        borderCollapse: "collapse",
                        border: "1px solid #ccc",
                    }}
                >
                    <thead>
                        <tr>
                            <th
                                style={{
                                    width: "94%",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                            >
                                Syarat Kelengkapan
                            </th>
                            <th
                                style={{
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                            >
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        {alertData?.data?.data?.length > 0 ? (
                            alertData.data.data.map((alert, index) => (
                                <tr key={index}>
                                    <td
                                        style={{
                                            border: "1px solid #ccc",
                                            padding: "8px",
                                        }}
                                    >
                                        {alert.description}
                                    </td>
                                    <td
                                        style={{
                                            border: "1px solid #ccc",
                                            padding: "8px",
                                        }}
                                    >
                                        <Button type="primary" size="small">
                                            Hapus
                                        </Button>
                                    </td>
                                </tr>
                            ))
                        ) : (
                            <tr>
                                <td
                                    colSpan={2}
                                    style={{
                                        textAlign: "center",
                                        border: "1px solid #ccc",
                                        padding: "8px",
                                    }}
                                >
                                    Tidak ada data.
                                </td>
                            </tr>
                        )}
                    </tbody>
                </table>
            </Modal>
        </>
    );
};

export default ModalAlert;
