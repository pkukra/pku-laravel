import React, { useState, useEffect } from "react";
import { Modal, Select, Card, Button, notification } from "antd";

export default function Index({ pasien, reFetchPasien, pasienLoading }) {
    const [loadingSave, setLoadingSave] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);
    const [selectedCaraMasuk, setSelectedCaraMasuk] = useState(
        pasien?.CARA_MASUK || ""
    );
    const [selectedCaraPulang, setSelectedCaraPulang] = useState(
        pasien?.CARA_PULANG || ""
    );

    const caraMasukMap = {
        gp: "Rujukan FKTP",
        "hosp-trans": "Rujukan FKRTL",
        mp: "Rujukan Spesialis",
        outp: "Dari Rawat Jalan",
        inp: "Dari Rawat Inap",
        emd: "Dari Rawat Darurat",
        born: "Lahir di RS",
        nursing: "Rujukan Panti Jompo",
        psych: "Rujukan dari RS Jiwa",
        rehab: "Rujukan Fasilitas Rehab",
        other: "Lain-lain",
    };

    const caraMasuk = (kode) => caraMasukMap[kode] || "Tidak Diketahui";

    const caraMasukOptions = [
        { value: "", label: "Pilih Cara Masuk" }, // Opsi default
        ...Object.entries(caraMasukMap).map(([value, label]) => ({
            value,
            label,
        })),
    ];

    const caraPulangMap = {
        1: "SEMBUH",
        2: "PERBAIKAN",
        3: "MENINGGAL <48 JAM",
        4: "MENINGGAL>=48",
        5: "ATAS PERMINTAAN SENDIRI",
        6: "LAIN- LAIN",
    };

    const handleOpenModal = () => {
        setSelectedCaraMasuk(pasien?.CARA_MASUK || "");
        setSelectedCaraPulang(pasien?.CARA_PULANG || "");
        setModalOpen(true);
    };

    // Fetch cara masuk bpjs
    const fetchSugestCaraMasuk = async () => {
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.cari_cara_masuk_bpjs")
            );
            console.log(response?.data);
        } catch (error) {
            console.error("Error fetching data:", error);
        } finally {
        }
    };

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

    fetchSugestCaraMasuk(() => {
        fetchSugestCaraMasuk();
    }, []);

    return (
        <>
            <Card title="Cara Masuk & Pulang" loading={pasienLoading}>
                <table style={{ width: "100%" }}>
                    <tbody>
                        <tr>
                            <td style={{ width: "20%" }}>Cara Masuk</td>
                            <td>: {caraMasuk(pasien?.CARA_MASUK)}</td>
                        </tr>
                        <tr>
                            <td>Cara Pulang</td>
                            <td>: {caraMasuk(pasien?.CARA_MASUK)}</td>
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
