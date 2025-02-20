import React, { useState } from "react";
import { Modal, Select, Card, Button } from "antd";

export default function Index({ pasien }) {
    const [modalCaraMasukOpen, setModalCaraMasukOpen] = useState(false);
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

    const handleOpenModal = () => {
        setSelectedCaraMasuk(pasien?.CARA_MASUK || "");
        setSelectedCaraPulang(pasien?.CARA_PULANG || "");
        setModalCaraMasukOpen(true);
    };
    
    const handleSave = () => {
        alert(selectedCaraMasuk)
    };

    return (
        <>
            <Card title="Cara Masuk & Pulang">
                <table style={{ width: "100%" }}>
                    <tbody>
                        <tr>
                            <td style={{ width: "20%" }}>Cara Masuk</td>
                            <td>: {caraMasuk(pasien?.CARA_MASUK)}</td>
                        </tr>
                        <tr>
                            <td>Cara Pulang</td>
                            <td>: {caraMasuk(pasien?.CARA_PULANG)}</td>
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
                destroyOnClose
                title="Ubah Cara Masuk dan Pulang"
                open={modalCaraMasukOpen}
                onCancel={() => setModalCaraMasukOpen(false)}
                footer={[
                    <Button onClick={() => setModalCaraMasukOpen(false)}>
                        Batal
                    </Button>,
                    <Button type="primary" onClick={handleSave}>
                        Ubah
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
