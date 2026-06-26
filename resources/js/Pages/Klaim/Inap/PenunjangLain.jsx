import React, { useState, useEffect } from "react";
import { Button, Modal, Skeleton, Form, Input, notification, List, Space, Typography } from "antd";
import axios from "axios";

const { Text } = Typography;

export default function Index({ kode_reg }) {
    const [modalOpen, setModalOpen] = useState(false);
    const [loadingList, setLoadingList] = useState(false);
    const [uploading, setUploading] = useState(false);
    const [namaPenunjang, setNamaPenunjang] = useState("");
    const [selectedFile, setSelectedFile] = useState(null);
    const [documents, setDocuments] = useState([]);

    const fetchDocuments = () => {
        setLoadingList(true);
        axios
            .get(route("klaim.inap.penunjang_lain.list", { kode_reg }))
            .then((response) => {
                setDocuments(response?.data?.data ?? []);
            })
            .catch((error) => {
                console.error("Error fetching dokumen penunjang:", error);
                notification.error({
                    message: "Gagal memuat dokumen",
                    description: "Tidak dapat mengambil data dokumen penunjang.",
                });
            })
            .finally(() => {
                setLoadingList(false);
            });
    };

    const openModal = () => {
        setModalOpen(true);
        fetchDocuments();
    };

    const handleFileChange = (event) => {
        const file = event.target.files?.[0] ?? null;
        setSelectedFile(file);
    };

    const handleUpload = async () => {
        if (!namaPenunjang.trim()) {
            return notification.error({
                message: "Nama dokumen wajib diisi",
            });
        }

        if (!selectedFile) {
            return notification.error({
                message: "Pilih file gambar atau PDF",
            });
        }

        const formData = new FormData();
        formData.append("nama_penunjang", namaPenunjang);
        formData.append("document_file", selectedFile);

        setUploading(true);

        try {
            const response = await axios.post(
                route("klaim.inap.penunjang_lain.upload", { kode_reg }),
                formData,
                {
                    headers: {
                        "Content-Type": "multipart/form-data",
                    },
                }
            );

            if (response?.data?.status === "ok") {
                notification.success({
                    message: "Upload berhasil",
                    description: "Dokumen penunjang berhasil disimpan.",
                });
                setNamaPenunjang("");
                setSelectedFile(null);
                fetchDocuments();
            } else {
                notification.error({
                    message: "Upload gagal",
                    description: response?.data?.message || "Tidak dapat menyimpan dokumen.",
                });
            }
        } catch (error) {
            console.error("Upload error:", error);
            notification.error({
                message: "Upload gagal",
                description: error?.response?.data?.message || error.message,
            });
        } finally {
            setUploading(false);
        }
    };

    const handleView = (item) => {
        window.open(
            route("klaim.inap.penunjang_lain.download", {
                kode_reg,
                id: item.ID,
            }),
            "_blank"
        );
    };

    const handleDelete = async (item) => {
        const confirmed = window.confirm("Hapus dokumen penunjang ini?");
        if (!confirmed) {
            return;
        }

        try {
            const response = await axios.delete(
                route("klaim.inap.penunjang_lain.delete", {
                    kode_reg,
                    id: item.ID,
                })
            );

            if (response?.data?.status === "ok") {
                notification.success({
                    message: "Berhasil dihapus",
                });
                fetchDocuments();
            } else {
                notification.error({
                    message: "Gagal menghapus",
                    description: response?.data?.message || "Tidak dapat menghapus dokumen.",
                });
            }
        } catch (error) {
            console.error("Delete error:", error);
            notification.error({
                message: "Gagal menghapus",
                description: error?.response?.data?.message || error.message,
            });
        }
    };

    return (
        <>
            <Button
                block
                size="small"
                onClick={openModal}
                style={{ margin: "2px" }}
            >
                Penunjang Lain
            </Button>

            <Modal
                title="Upload Penunjang Lain"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                footer={null}
                width={800}
                destroyOnClose
            >
                <Form layout="vertical">
                    <Form.Item label="Nama Dokumen" required>
                        <Input
                            value={namaPenunjang}
                            onChange={(event) => setNamaPenunjang(event.target.value)}
                            maxLength={50}
                            placeholder="Contoh: Foto Hasil Radiologi"
                        />
                    </Form.Item>

                    <Form.Item label="File (PDF / Gambar)" required>
                        <input
                            type="file"
                            accept=".pdf,image/*"
                            onChange={handleFileChange}
                            style={{ width: "100%" }}
                        />
                        {selectedFile ? (
                            <Text type="secondary">Dipilih: {selectedFile.name}</Text>
                        ) : (
                            <Text type="secondary">Pilih satu file untuk diupload.</Text>
                        )}
                    </Form.Item>

                    <Form.Item>
                        <Space>
                            <Button
                                type="primary"
                                onClick={handleUpload}
                                loading={uploading}
                                disabled={uploading}
                            >
                                Upload
                            </Button>
                            <Button
                                onClick={() => {
                                    setNamaPenunjang("");
                                    setSelectedFile(null);
                                }}
                            >
                                Reset
                            </Button>
                        </Space>
                    </Form.Item>
                </Form>

                <div style={{ marginTop: 24 }}>
                    <h4>Dokumen Penunjang Terupload</h4>
                    {loadingList ? (
                        <Skeleton active />
                    ) : (
                        <List
                            bordered
                            dataSource={documents}
                            locale={{ emptyText: "Belum ada dokumen penunjang." }}
                            renderItem={(item) => (
                                <List.Item
                                    actions={[
                                        <Button
                                            type="link"
                                            key="view"
                                            onClick={() => handleView(item)}
                                        >
                                            Lihat
                                        </Button>,
                                        <Button
                                            type="link"
                                            danger
                                            key="delete"
                                            onClick={() => handleDelete(item)}
                                        >
                                            Hapus
                                        </Button>,
                                    ]}
                                >
                                    <List.Item.Meta
                                        title={item.NAMA_PENUNJANG}
                                        description={item.FILE_NAME}
                                    />
                                </List.Item>
                            )}
                        />
                    )}
                </div>
            </Modal>
        </>
    );
}
