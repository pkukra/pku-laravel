import React, { useState, useEffect } from "react";
import { Modal, Spin, Card, Input, notification, Button } from "antd";
import { EditOutlined } from "@ant-design/icons";
import axios from "axios";

const { TextArea } = Input;

export default function Index({ pasien }) {
    const [fetchMrDiagnosaLoading, setFetchMrDiagnosaLoading] = useState(false);
    const [loadingSaveCatKhusus, setLoadingSaveCatKhusus] = useState(false);
    const [dataMrDiagnosa, setDataMrDiagnosa] = useState(null);
    const [modalCatatatnOpen, setModalCatatatnOpen] = useState(false);
    const [editedCatatanKhusus, setEditedCatatanKhusus] = useState("");

    // Fungsi untuk mengambil data diagnosa dari tabel MR_DIAGNOSA
    const fetchMRDiagnosa = () => {
        setFetchMrDiagnosaLoading(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_mr_diagnosa", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                })
            )
            .then((response) => {
                setDataMrDiagnosa(response.data.data);
                setEditedCatatanKhusus(
                    response.data.data?.MRCATATANKHUSUS || ""
                ); // Set initial value for editing
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setFetchMrDiagnosaLoading(false);
            });
        return;
    };

    useEffect(() => {
        fetchMRDiagnosa();
    }, []);

    const handleSaveCatatanKhusus = () => {
        setLoadingSaveCatKhusus(true);
        axios
            .post(
                route("rm.pasien-rujukan.update_catatan_khusus", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                }),
                {
                    catatan_khusus: editedCatatanKhusus,
                }
            )
            .then((response) => {
                if (response?.data?.status !== "ok") {
                    notification.error({
                        message: "Gagal",
                        description: "Catatan Khusus gagal disimpan",
                    });
                } else {
                    notification.success({
                        message: "Success",
                        description: "Catatan Khusus berhasil disimpan",
                    });
                }

                setLoadingSaveCatKhusus(false);
                fetchMRDiagnosa(); // Refresh the data to reflect changes
                setModalCatatatnOpen(false); // Close modal after saving
                setFetchMrDiagnosaLoading(false);

                return;
            })
            .catch((error) => {
                console.error("Error saving Catatan Khusus:", error);
                return notification.error({
                    message: "Error",
                    description:
                        "Terjadi kesalahan saat menyimpan Catatan Khusus",
                });
            });
    };

    return (
        <>
            <Card
                loading={fetchMrDiagnosaLoading}
                title={"Amnanese & Catatan Khusus"}
                style={{ marginBottom: 5 }}
            >
                <p>
                    <strong>Amnanese:</strong>
                </p>
                <p>{dataMrDiagnosa?.MRDDIAGNOSA_UTAMA}</p>
                <p>
                    <strong>Catatan Khusus:</strong>
                </p>
                <p>
                    {dataMrDiagnosa?.MRCATATANKHUSUS} {"   "}
                </p>
                <Button
                    type="primary"
                    icon={<EditOutlined />}
                    size="small"
                    onClick={() => setModalCatatatnOpen(true)}
                >
                    Add/Edit Catatan
                </Button>
            </Card>

            <Modal
                title="Edit Catatatan Khusus"
                open={modalCatatatnOpen}
                onCancel={() => setModalCatatatnOpen(false)}
                onOk={handleSaveCatatanKhusus} // Trigger save when user clicks OK
                confirmLoading={loadingSaveCatKhusus}
            >
                <TextArea
                    rows={4}
                    value={editedCatatanKhusus}
                    onChange={(e) => setEditedCatatanKhusus(e.target.value)} // Update the state with the new value
                />
            </Modal>
        </>
    );
}
