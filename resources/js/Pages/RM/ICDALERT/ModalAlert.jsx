import React, { useState } from "react";
import { Modal, Button, Input, message, Space, Spin } from "antd";
import axios from "axios";

const { confirm } = Modal;
const { TextArea } = Input;

const ModalAlert = ({ dataCode }) => {
    const [loadingTable, setLoadingTable] = useState(false); // loading tabel
    const [loadingCrud, setLoadingCrud] = useState(false); // loading saat CRUD
    const [modalAlertOpen, setModalAlertOpen] = useState(false);
    const [alertData, setAlertData] = useState([]);
    const [editingIndex, setEditingIndex] = useState(null);
    const [editingValue, setEditingValue] = useState("");
    const [newAlert, setNewAlert] = useState("");

    const code = dataCode?.code || null;

    const fetchDataAlert = async () => {
        setModalAlertOpen(true);
        setLoadingTable(true);
        try {
            const response = await axios.get(
                route("rm.icd.list_alert", { code })
            );
            setAlertData(response?.data?.data?.data || []);
        } catch (error) {
            console.error(error);
            message.error("Gagal memuat data alert");
        } finally {
            setLoadingTable(false);
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
        setLoadingCrud(true);
        try {
            await axios.put(route("rm.icd.update_alert", { id: alertId }), {
                description: editingValue,
            });
            message.success("Perubahan berhasil disimpan");
            fetchDataAlert();
        } catch (err) {
            console.error(err);
            message.error("Gagal menyimpan data");
        } finally {
            cancelEdit();
            setLoadingCrud(false);
        }
    };

    const handleSave = (alertId) => {
        confirm({
            title: "Yakin ingin menyimpan perubahan?",
            content: "Perubahan akan disimpan dan data di-refresh dari server.",
            okText: "Ya, Simpan",
            cancelText: "Batal",
            onOk: () => handleSaveConfirmed(alertId),
        });
    };

    const handleDelete = (alertId) => {
        confirm({
            title: "Yakin ingin menghapus data?",
            content: "Data ini akan dihapus dan data di-refresh dari server.",
            okText: "Ya, Hapus",
            cancelText: "Batal",
            onOk: async () => {
                setLoadingCrud(true);
                try {
                    await axios.delete(
                        route("rm.icd.delete_alert", { id: alertId })
                    );
                    message.success("Data berhasil dihapus");
                    fetchDataAlert();
                } catch (err) {
                    console.error(err);
                    message.error("Gagal menghapus data");
                } finally {
                    setLoadingCrud(false);
                }
            },
        });
    };

    const handleAddAlert = async () => {
        if (!newAlert.trim()) {
            message.warning("Data tidak boleh kosong");
            return;
        }
        setLoadingCrud(true);
        try {
            await axios.post(route("rm.icd.save_alert"), {
                icd_code: code,
                description: newAlert,
            });
            message.success("Alert baru berhasil ditambahkan");
            setNewAlert("");
            fetchDataAlert();
        } catch (err) {
            console.error(err);
            message.error("Gagal menambahkan alert");
        } finally {
            setLoadingCrud(false);
        }
    };

    return (
        <>
            <Button onClick={fetchDataAlert}>Tampilkan {code}</Button>
            <Modal
                destroyOnClose
                title="Tambahkan Syarat Kode ICD"
                open={modalAlertOpen}
                onCancel={() => {
                    setModalAlertOpen(false);
                    setAlertData([]);
                    cancelEdit();
                    setNewAlert("");
                }}
                footer={null}
                width={1000}
            >
                <p>
                    Kode: <strong>{code}</strong>
                </p>
                <p>
                    Desc: <strong>{dataCode.description}</strong>
                </p>

                {/* TextArea untuk input alert baru */}
                <TextArea
                    rows={4}
                    placeholder="Tambahkan alert baru di sini..."
                    value={newAlert}
                    onChange={(e) => setNewAlert(e.target.value)}
                    disabled={loadingCrud}
                />
                <Button
                    type="primary"
                    style={{ marginTop: 8, marginBottom: 16 }}
                    onClick={handleAddAlert}
                    loading={loadingCrud}
                >
                    Tambah Data
                </Button>

                {loadingTable ? (
                    <div style={{ textAlign: "center", padding: 20 }}>
                        <Spin />
                    </div>
                ) : (
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
                                    Syarat/Kelengkapan Data Pengkodean
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
                                                    disabled={loadingCrud}
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
                                                                handleSave(
                                                                    alert.id
                                                                )
                                                            }
                                                            loading={
                                                                loadingCrud
                                                            }
                                                        >
                                                            Simpan
                                                        </Button>
                                                        <Button
                                                            size="small"
                                                            onClick={cancelEdit}
                                                            disabled={
                                                                loadingCrud
                                                            }
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
                                                        disabled={loadingCrud}
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
                                                    loading={loadingCrud}
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
                                        colSpan={3}
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
                )}
            </Modal>
        </>
    );
};

export default ModalAlert;
