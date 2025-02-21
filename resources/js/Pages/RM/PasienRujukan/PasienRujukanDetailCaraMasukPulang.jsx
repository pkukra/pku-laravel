import React, { useState, useEffect } from "react";
import { Modal, Select, Card, Button, notification } from "antd";

export default function Index({ pasien, reFetchPasien, pasienLoading }) {
    const [loadingSave, setLoadingSave] = useState(false);
    const [caraMasukOptions, setCaraMasukOptions] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedCaraMasuk, setSelectedCaraMasuk] = useState(
        pasien?.CARA_MASUK || ""
    );
    const [selectedCaraPulang, setSelectedCaraPulang] = useState(
        pasien?.CARA_PULANG || ""
    );

    const handleOpenModal = () => {
        setSelectedCaraMasuk(pasien?.CARA_MASUK || "");
        setSelectedCaraPulang(pasien?.CARA_PULANG || "");
        setModalOpen(true);
    };

    // Fetch cara masuk bpjs
    async function fetchSugestCaraMasuk() {
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.cari_cara_masuk_bpjs")
            );
            const data = response?.data?.data || [];

            const caraMasukOptions = data.map((item) => ({
                value: item.KODE,
                label: item.KETERANGAN,
            }));

            setCaraMasukOptions(caraMasukOptions);
        } catch (error) {
            console.error("Error fetching data:", error);
        }
    }

    const handleSave = () => {
        setLoadingSave(true);
        axios
            .post(
                route("rm.pasien-rujukan.update_cara_masuk_pulang", {
                    kode_reg: pasien.FRPNOTRANSAKSI,
                }),
                {
                    cara_masuk: selectedCaraMasuk,
                    cara_pulang: "selectedCaraPulang",
                }
            )
            .then((response) => {
                if (response?.data?.status !== "ok") {
                    notification.error({
                        message: "Gagal",
                        description: "Gagal disimpan",
                    });
                } else {
                    notification.success({
                        message: "Success",
                        description: "Berhasil disimpan",
                    });
                }
            })
            .catch((error) => {
                console.error("Error saving :", error);
            })
            .finally(() => {
                setLoadingSave(false);
                setModalOpen(false);
                reFetchPasien();
            });
    };

    useEffect(() => {
        fetchSugestCaraMasuk();
    }, []);

    console.log(pasien);

    return (
        <>
            <Card title="Cara Masuk & Pulang" loading={pasienLoading}>
                <table style={{ width: "100%" }}>
                    <tbody>
                        <tr>
                            <td style={{ width: "20%" }}>Cara Masuk</td>
                            <td>: {(pasien?.CARA_MASUK_BPJS) ?? <>Belum diisi</>}</td>
                        </tr>
                        <tr>
                            <td>Cara Pulang</td>
                            <td>: {(pasien?.CARA_MASUK_BPJS) ?? <>Belum diisi</>}</td>
                        </tr>
                        <tr>
                            <td></td>
                            <td>
                                <Button
                                    type="primary"
                                    onClick={handleOpenModal}
                                >
                                    Ubah
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>

            <Modal
                closable={false}
                destroyOnClose
                title="Ubah Cara Masuk dan Pulang"
                open={modalOpen}
                onCancel={() => setModalOpen(false)}
                footer={[
                    <Button
                        onClick={() => setModalOpen(false)}
                        key={"Batal"}
                        loading={loadingSave}
                        disabled={loadingSave}
                    >
                        Batal
                    </Button>,
                    <Button
                        type="primary"
                        onClick={handleSave}
                        key={"Simpan"}
                        loading={loadingSave}
                        disabled={loadingSave}
                    >
                        Simpan
                    </Button>,
                ]}
            >
                <label>Cara Masuk:</label>
                <Select
                    value={selectedCaraMasuk}
                    style={{ width: "100%", marginBottom: "10px" }}
                    onChange={setSelectedCaraMasuk}
                    options={caraMasukOptions}
                />

                <label>Cara Pulang:</label>
                <Select
                    value={selectedCaraPulang}
                    style={{ width: "100%" }}
                    onChange={setSelectedCaraPulang}
                    options={caraMasukOptions}
                />
            </Modal>
        </>
    );
}
