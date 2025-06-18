import React, { useState, useEffect } from "react";
import { Modal, Select, Card, Button, notification, Input, InputNumber  } from "antd";
const { TextArea } = Input;

const perawatanOptions = [
    { value: 0, label: "Dirawat" },
    { value: 1, label: "Dirujuk" },
    { value: 2, label: "Pulang" },
    { value: 3, label: "Meninggal" },
];

export default function Index({ pasien, reFetchPasien, pasienLoading }) {
    const [loadingSave, setLoadingSave] = useState(false);

    const [keadaanKeluar, setKeadaanKeluar] = useState(null); //actual keadaan keluar rs dari database
    const [keadaanKeluarLoading, setKeadaanKeluarLoading] = useState(false); //loading actual keadaan keluar rs dari database
    const [kunjunganPasien, setKunjunganPasien] = useState(null); //actual keadaan keluar rs dari database

    const [caraMasukOptions, setCaraMasukOptions] = useState(false);
    const [RSRujukanOptions, setRSRujukanOptions] = useState(false);
    const [keadaanKeluarOptions, setKeadaanKeluarOptions] = useState(false);
    const [modalOpen, setModalOpen] = useState(false);

    const [selectedCaraMasuk, setSelectedCaraMasuk] = useState(
        pasien?.CARA_MASUK || null
    );
    const [selectedKeadaanKeluar, setSelectedKeadaanKeluar] = useState(null);
    const [selectedSebabKematian, setSelectedSebabKematian] = useState(null);
    const [selectedKeperawatan, setSelectedKeperawatan] = useState(null);
    const [selectedRSRujukanKeluar, setSelectedRSRujukanKeluar] =
        useState(null);
    const [beratLahir, setBeratLahir] = useState(null);
    const [sitb, setSitb] = useState(null);

    const handleOpenModal = () => {
        setSelectedCaraMasuk(pasien?.CARA_MASUK || "");
        setModalOpen(true);
    };

    // Fetch actual keadaan keluar rs
    async function fetchActualKeadaanKelauarRS() {
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.get_keadaan_keluar_rs", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                })
            );
            const data = response?.data?.data || [];
            setKeadaanKeluar(data);
            setSelectedKeadaanKeluar(data.MRKKEADAAN_KELUAR);
            setSelectedSebabKematian(data.MRKSEBAB);
        } catch (error) {
            console.error("Error fetching data:", error);
        }
        return;
    }

    // Fetch actual keadaan keluar rs
    async function fetchActualKunjunganPasien() {
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.get_kunjungan_pasien", {
                    kode_reg: pasien.FRPNOTRANSAKSIKJ,
                })
            );
            const data = response?.data?.data || null;

            setSelectedKeperawatan(data?.KPPERAWATAN);
            setSelectedRSRujukanKeluar(data?.KPRUJUKLUAR);

            setKunjunganPasien(data);
        } catch (error) {
            console.error("Error fetching data:", error);
        }
        return;
    }

    // Fetch options cara masuk bpjs for selectbox
    async function fetchSugestCaraMasuk() {
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.cari_cara_masuk_bpjs")
            );
            const data = response?.data?.data || [];

            const options = data.map((item) => ({
                value: item.KODE,
                label: item.KETERANGAN,
            }));

            setCaraMasukOptions(options);
        } catch (error) {
            console.error("Error fetching data:", error);
        }
    }

    // Fetch options keadaan keluar rs for selectbox
    async function fetchSugestKeadaanKelauarRS() {
        setKeadaanKeluarLoading(true);
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.cari_keadaan_keluar_rs")
            );
            const data = response?.data?.data || [];
            const options = data.map((item) => ({
                value: item.FMKKRSKODE,
                label: item.FMKKRSKETERANGAN,
            }));

            setKeadaanKeluarOptions(options);
        } catch (error) {
            console.error("Error fetching data:", error);
        }
        setKeadaanKeluarLoading(false);
    }

    // Fetch options rs rujukan keluar
    async function fetchSugestRSRujukan() {
        try {
            const response = await axios.get(
                route("rm.pasien-rujukan.cari_rs_rujukan")
            );
            const data = response?.data?.data || [];
            const options = data.map((item) => ({
                value: item.MRKODERUJUKAN,
                label: item.MRKODERUJUKANN,
            }));
            setRSRujukanOptions(options);
        } catch (error) {
            console.error("Error fetching data:", error);
        }
    }

    const handleSave = () => {
        setLoadingSave(true);
        axios
            .post(
                route("rm.pasien-rujukan.update_keperawatan", {
                    kode_reg_kj: pasien.FRPNOTRANSAKSIKJ,
                }),
                {
                    kode_pasien: pasien.FRPPASIEN_ID,
                    kode_unit: pasien.FRPUNIT,
                    kode_dokter: pasien.FRPDOKTER_ID,
                    tgl_masuk: pasien.FRPTGL,
                    cara_masuk: selectedCaraMasuk,

                    kode_rs_rujuk_keluar: selectedRSRujukanKeluar,
                    keperawatan: selectedKeperawatan,

                    keadaan_keluar: selectedKeadaanKeluar,
                    sebab_kematian: selectedSebabKematian,

                    berat_lahir: beratLahir,
                    sitb: sitb,
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
                notification.error({
                    message: "Error",
                    description: "Terjadi kesalahan",
                });
            })
            .finally(() => {
                setBeratLahir(pasien?.BBL);
                setSitb(pasien?.SITB);
                setLoadingSave(false);
                setModalOpen(false);
                reFetchPasien();
                fetchActualKeadaanKelauarRS();
                fetchActualKunjunganPasien();
            });
    };

    useEffect(() => {
        setBeratLahir(pasien?.BBL);
        setSitb(pasien?.SITB);
        fetchSugestRSRujukan();
        fetchActualKunjunganPasien();
        fetchActualKeadaanKelauarRS();
        fetchSugestCaraMasuk();
        fetchSugestKeadaanKelauarRS();
    }, [pasien]);

    // Fungsi untuk mendapatkan label berdasarkan value
    const statusPerawatan = (kode) => {
        const found = perawatanOptions.find((opt) => opt.value == kode);
        return found ? found.label : "Tidak Diketahui";
    };

    // Fungsi untuk mendapatkan label berdasarkan value
    const statusRujukanKeluar = (kode) => {
        if (!RSRujukanOptions) {
            return "Fetching data...";
        }
        const found = RSRujukanOptions.find((opt) => opt.value == kode);
        return found ? found.label : "Tidak ada rujukan";
    };

    return (
        <>
            <Card
                title="Cara Masuk & Pulang"
                loading={pasienLoading || keadaanKeluarLoading}
            >
                <table style={{ width: "100%" }}>
                    <tbody>
                        <tr>
                            <td style={{ width: "25%" }}>Berat Lahir (gram)</td>
                            <td>: {pasien?.BBL}</td>
                        </tr>

                        <tr>
                            <td>SITB</td>
                            <td>: {pasien?.SITB}</td>
                        </tr>

                        <tr>
                            <td>Cara Masuk</td>
                            <td>
                                : {pasien?.CARA_MASUK_BPJS ?? <>Belum diisi</>}
                            </td>
                        </tr>
                        {/* <tr>
                            <td>Keperawatan</td>
                            <td>
                                :{" "}
                                {kunjunganPasien?.KPPERAWATAN ? (
                                    statusPerawatan(
                                        kunjunganPasien?.KPPERAWATAN
                                    )
                                ) : (
                                    <>Belum diisi</>
                                )}
                            </td>
                        </tr> */}

                        <tr>
                            <td>Keadaan Keluar RS</td>
                            <td>: {keadaanKeluar?.FMKKRSKETERANGAN}</td>
                        </tr>

                        {selectedKeadaanKeluar == 7 && (
                            <tr>
                                <td>Rujukan Keluar</td>
                                <td>
                                    :{" "}
                                    {statusRujukanKeluar(
                                        kunjunganPasien?.KPRUJUKLUAR
                                    )}
                                </td>
                            </tr>
                        )}

                        {(selectedKeadaanKeluar == 4 ||
                            selectedKeadaanKeluar == 3) && (
                            <tr>
                                <td>Sebab Kematian</td>
                                <td>: {keadaanKeluar?.MRKSEBAB}</td>
                            </tr>
                        )}
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
                <label>Berat Lahir (gram):</label> <br />
                <InputNumber
                    value={beratLahir}
                    onChange={setBeratLahir}
                    min={0}
                    style={{ width: "100%" }}
                />{" "}
                <br />
                <label>No SITB:</label>
                <Input
                    value={sitb}
                    onChange={(e) => {
                        setSitb(e.target.value);
                    }}
                    style={{ width: "100%" }}
                />{" "}
                <br />
                <label>Cara Masuk:</label>
                <Select
                    value={selectedCaraMasuk}
                    style={{ width: "100%", marginBottom: "10px" }}
                    onChange={setSelectedCaraMasuk}
                    options={caraMasukOptions}
                />
                {/* <label>Perawatan:</label>
                <Select
                    value={parseInt(selectedKeperawatan)}
                    style={{ width: "100%", marginBottom: "10px" }}
                    onChange={(value) =>
                        setSelectedKeperawatan(parseInt(value))
                    }
                    options={perawatanOptions}
                />*/}
                <label>Keadaan Keluar RS: </label>
                <Select
                    value={selectedKeadaanKeluar}
                    style={{ width: "100%" }}
                    onChange={setSelectedKeadaanKeluar}
                    options={keadaanKeluarOptions}
                />
                {(selectedKeadaanKeluar == 4 || selectedKeadaanKeluar == 3) && (
                    <>
                        <label>Sebab Kematian: </label>
                        <TextArea
                            disabled={
                                !(
                                    selectedKeadaanKeluar == 4 ||
                                    selectedKeadaanKeluar == 3
                                )
                            }
                            rows={4}
                            placeholder="Sebab Kematian"
                            value={selectedSebabKematian}
                            onChange={(e) =>
                                setSelectedSebabKematian(e.target.value)
                            }
                        />
                    </>
                )}
                {selectedKeadaanKeluar == 7 && (
                    <>
                        <label>RS Tujuan: </label>
                        <Select
                            value={selectedRSRujukanKeluar}
                            onChange={(value) =>
                                setSelectedRSRujukanKeluar(value)
                            }
                            disabled={!(selectedKeadaanKeluar == 7)}
                            style={{ width: "100%", marginBottom: "10px" }}
                            options={RSRujukanOptions}
                        />
                    </>
                )}
            </Modal>
        </>
    );
}
