import React, { useState, useEffect } from "react";
import {
    Modal,
    Button,
    message,
    Space,
    Spin,
    Radio,
    Flex,
    notification,
} from "antd";
import axios from "axios";
import ReactQuill from "react-quill";
import "react-quill/dist/quill.snow.css";

const { confirm } = Modal;

const ModalAlert = ({ dataCode }) => {
    const [loadingTable, setLoadingTable] = useState(false);
    const [loadingCrud, setLoadingCrud] = useState(false);
    const [modalAlertOpen, setModalAlertOpen] = useState(false);
    const [alertData, setAlertData] = useState([]);
    const [editingIndex, setEditingIndex] = useState(null);
    const [editingValue, setEditingValue] = useState("");
    const [newAlert, setNewAlert] = useState("");

    const [detailCode, setDetailCode] = useState(null);
    const [isWarning, setIsWarning] = useState("0");

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
        const previousValue = isWarning;

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
                setIsWarning(newValue);
                try {
                    await axios.post(route("rm.icd.update_warning", { id }), {
                        is_code_warning: numericValue,
                    });
                    message.success("Perubahan berhasil disimpan");
                    if (fetchDataAlert) fetchDataAlert();
                } catch (err) {
                    console.error(err);
                    message.error("Gagal menyimpan data");
                    setIsWarning(previousValue);
                }
            },
            onCancel: () => {
                setIsWarning(previousValue);
            },
        });
    };

    useEffect(() => {
        if (detailCode) {
            setIsWarning(detailCode.is_code_warning == 1 ? "1" : "0");
        }
    }, [detailCode]);

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
                        disabled={loadingCrud}
                    >
                        <Radio.Button value="0">
                            Tidak Rawan Pending
                        </Radio.Button>

                        <Radio.Button
                            value="1"
                            style={{
                                backgroundColor:
                                    isWarning === "1" ? "#ff4d4f" : undefined,
                                color: isWarning === "1" ? "white" : undefined,
                                borderColor:
                                    isWarning === "1" ? "#ff4d4f" : undefined, // border merah
                                boxShadow:
                                    isWarning === "1" ? "none" : undefined, // hilangkan efek biru
                            }}
                        >
                            Rawan Pending
                        </Radio.Button>
                    </Radio.Group>
                </Flex>

                {/* ReactQuill untuk input alert baru */}
                <ReactQuill
                    theme="snow"
                    value={newAlert}
                    onChange={setNewAlert}
                    readOnly={loadingCrud}
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
                                                <ReactQuill
                                                    theme="snow"
                                                    value={editingValue}
                                                    onChange={setEditingValue}
                                                    readOnly={loadingCrud}
                                                />
                                            ) : (
                                                <div
                                                    dangerouslySetInnerHTML={{
                                                        __html: alert.description,
                                                    }}
                                                />
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
