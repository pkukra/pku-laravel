import React, { useState, useEffect } from "react";
import {
    Modal,
    Button,
    Input,
    message,
    Space,
    Spin,
    Radio,
    Flex,
    notification,
} from "antd";
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

    const [detailCode, setDetailCode] = useState(null);
    const [isWarning, setIsWarning] = useState("0"); // default value radio rawan pending

    const code = dataCode?.code || null;

    const fetchDetailCode = async () => {
        try {
            const response = await axios.get(
                route("rm.icd.detail_icd_data", { code })
            );
            setDetailCode(response?.data?.data || null);
        } catch (error) {
            console.error(error);
            message.error("Gagal memuat data code icd");
        } finally {
            setLoadingTable(false);
        }
    };

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

    const handleChangeRawanPending = (e) => {
        const newValue = e.target.value;
        const numericValue = newValue === "1" ? 1 : 0;
        const previousValue = isWarning; // simpan nilai lama

        const id = detailCode?.id;
        if (!id) {
            return notification.error({
                placement: "top",
                description: "Gagal mendapatkan detail kode ICD.",
            });
        }

        Modal.confirm({
            title: "Konfirmasi",
            content: "Yakin ingin mengubah status Rawan Pending?",
            okText: "Ya",
            cancelText: "Batal",
            onOk: async () => {
                setIsWarning(newValue); // update state
                try {
                    await axios.post(route("rm.icd.update_warning", { id }), {
                        is_code_warning: numericValue,
                    });
                    message.success("Perubahan berhasil disimpan");
                    if (fetchDataAlert) fetchDataAlert();
                } catch (err) {
                    console.error(err);
                    message.error("Gagal menyimpan data");
                    setIsWarning(previousValue); // rollback jika gagal
                }
            },
            onCancel: () => {
                setIsWarning(previousValue); // rollback ke nilai lama
            },
        });
    };

    useEffect(() => {
        if (detailCode) {
            setIsWarning(detailCode.is_code_warning == 1 ? "1" : "0");
        }
    }, [detailCode]); // update setiap kali detailCode berubah

    return (
        <>
            <Button
                onClick={() => {
                    fetchDetailCode();
                    fetchDataAlert();
                }}
            >
                Tampilkan {code}
            </Button>
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
                <Flex vertical gap="middle" style={{ marginBottom: 16 }}>
                    <Radio.Group
                        value={isWarning}
                        buttonStyle="solid"
                        onChange={handleChangeRawanPending}
                        disabled={loadingCrud} // optional, agar tidak bisa klik saat CRUD
                    >
                        <Radio.Button value="0">
                            Tidak Rawan Pending
                        </Radio.Button>
                        <Radio.Button value="1">Rawan Pending</Radio.Button>
                    </Radio.Group>
                </Flex>

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
                    Tambahkan Data
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
