import React, { useState } from "react";
import { Modal, Button, Input, message, Space } from "antd";
import axios from "axios";

const { confirm } = Modal;
const { TextArea } = Input;

const ModalAlert = ({ dataCode }) => {
    const [loadingAlert, setLoadingAlert] = useState(false);
    const [modalAlertOpen, setModalAlertOpen] = useState(false);
    const [alertData, setAlertData] = useState([]);
    const [editingIndex, setEditingIndex] = useState(null);
    const [editingValue, setEditingValue] = useState("");

    const code = dataCode?.code || null;

    const fetchDataAlert = async () => {
        setModalAlertOpen(true);
        setLoadingAlert(true);
        try {
            const response = await axios.get(
                route("rm.icd.list_alert", { code })
            ); // API asli
            setAlertData(response?.data?.data?.data || []);
        } catch (error) {
            console.error("Error fetching data:", error);
            message.error("Gagal memuat data alert");
        } finally {
            setLoadingAlert(false);
        }
    };

    const startEdit = (index, value) => {
        setEditingIndex(index);
        setEditingValue(value);
    };

    const cancelEdit = () => {
        setEditingIndex(null);
        setEditingValue("");
    };

    const handleSaveConfirmed = async (alertId) => {
        try {
            // Simulasi update dengan dummy API
            await axios.put(
                `https://jsonplaceholder.typicode.com/posts/${alertId}`,
                {
                    description: editingValue,
                }
            );
            message.success("Perubahan berhasil disimpan (dummy)");

            // Refresh data dari API asli
            fetchDataAlert();
        } catch (err) {
            message.error("Gagal menyimpan data (dummy)");
        } finally {
            cancelEdit();
        }
    };

    const handleSave = (alertId) => {
        confirm({
            title: "Yakin ingin menyimpan perubahan?",
            content:
                "Perubahan akan disimpan (dummy API) dan data di-refresh dari server.",
            okText: "Ya, Simpan",
            cancelText: "Batal",
            onOk: () => handleSaveConfirmed(alertId),
        });
    };

    const handleDelete = (alertId) => {
        confirm({
            title: "Yakin ingin menghapus data?",
            content:
                "Data ini akan dihapus (dummy API) dan data di-refresh dari server.",
            okText: "Ya, Hapus",
            cancelText: "Batal",
            onOk: async () => {
                try {
                    // Simulasi delete dengan dummy API
                    await axios.delete(
                        `https://jsonplaceholder.typicode.com/posts/${alertId}`
                    );
                    message.success("Data berhasil dihapus (dummy)");

                    // Refresh data dari API asli
                    fetchDataAlert();
                } catch (err) {
                    message.error("Gagal menghapus data (dummy)");
                }
            },
        });
    };

    return (
        <>
            <Button onClick={fetchDataAlert}>Tampilkan {code}</Button>
            <Modal
                destroyOnClose
                title="Tambahkan Alert Kode ICD"
                open={modalAlertOpen}
                onCancel={() => {
                    setModalAlertOpen(false);
                    setAlertData([]);
                    cancelEdit();
                }}
                footer={null}
                width={1000}
                confirmLoading={loadingAlert}
            >
                <p>Kode: <strong>{code}</strong></p>
                <p>Desc: <strong>{dataCode.description}</strong></p>
                <table
                    style={{
                        width: "100%",
                        borderCollapse: "collapse",
                        border: "1px solid #ccc",
                    }}
                >
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th
                                style={{
                                    width: "78%",
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
                        {alertData.length > 0 ? (
                            alertData.map((alert, index) => (
                                <tr key={alert.id}>
                                    <td
                                        style={{
                                            border: "1px solid #ccc",
                                            padding: "8px",
                                        }}
                                    >
                                        {alert.id}
                                    </td>
                                    <td
                                        style={{
                                            border: "1px solid #ccc",
                                            padding: "8px",
                                        }}
                                    >
                                        {editingIndex === index ? (
                                            <TextArea
                                                rows={4}
                                                value={editingValue}
                                                onChange={(e) =>
                                                    setEditingValue(
                                                        e.target.value
                                                    )
                                                }
                                                autoFocus
                                            />
                                        ) : (
                                            <pre
                                                style={{
                                                    whiteSpace: "pre-wrap",
                                                    margin: 0,
                                                }}
                                            >
                                                {alert.description}
                                            </pre>
                                        )}
                                    </td>
                                    <td
                                        style={{
                                            border: "1px solid #ccc",
                                            padding: "8px",
                                        }}
                                    >
                                        <Space>
                                            {editingIndex === index ? (
                                                <>
                                                    <Button
                                                        type="primary"
                                                        size="small"
                                                        onClick={() =>
                                                            handleSave(alert.id)
                                                        }
                                                    >
                                                        Simpan
                                                    </Button>
                                                    <Button
                                                        size="small"
                                                        onClick={cancelEdit}
                                                    >
                                                        Batal
                                                    </Button>
                                                </>
                                            ) : (
                                                <Button
                                                    size="small"
                                                    onClick={() =>
                                                        startEdit(
                                                            index,
                                                            alert.description
                                                        )
                                                    }
                                                >
                                                    Edit
                                                </Button>
                                            )}
                                            <Button
                                                danger
                                                size="small"
                                                onClick={() =>
                                                    handleDelete(alert.id)
                                                }
                                            >
                                                Hapus
                                            </Button>
                                        </Space>
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
