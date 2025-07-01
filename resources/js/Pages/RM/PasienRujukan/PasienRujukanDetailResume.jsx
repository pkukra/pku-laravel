import React, { useState, useEffect } from "react";
import { Card, Button, Modal } from "antd";

const cleanText = (html) =>
    html
        .replace(/<\/?p>/g, "") // Hapus tag <p> dan </p>
        .replace(/&nbsp;/g, " ") // Ganti &nbsp; dengan spasi
        .replace(/\s+/g, " ") // Hapus enter & spasi berlebih
        .trim(); // Hapus spasi di awal dan akhir

export default function Index({ pasien }) {
    const [resumeData, setResumeData] = useState(null);
    const [loadingResume, setLoadingResume] = useState(false);
    const [sugestDariAi, setSugestDariAi] = useState([]);
    const [modalAiOpen, setModalAiOpen] = useState(false);
    const [loadingFetchingSugestKodeAI, setLoadingFetchingSugestKodeAI] =
        useState(false);

    const fetchResume = async () => {
        setLoadingResume(true);
        axios
            .get(
                route("rm.pasien-rujukan.get_resume", {
                    kode_reg: pasien.FRPNOTRANSAKSI,
                })
            )
            .then((response) => {
                setResumeData(response?.data?.data || null);
            })
            .catch((error) => {
                console.error("Error fetching diagnosa data:", error);
            })
            .finally(() => {
                setLoadingResume(false);
            });
    };

    const handleGetAiSugest = async () => {
        setModalAiOpen(true);
        setLoadingFetchingSugestKodeAI(true);
        const anamnesa = cleanText(resumeData?.FS_ANAMNESA || "");
        const diagnosa = cleanText(resumeData?.FS_DIAGNOSA || "");

        try {
            const response = await axios.post(
                "http://10.10.10.225:3000/get-icd10",
                {
                    anamnesa: anamnesa,
                    diagnosa: diagnosa,
                }
            );
            setSugestDariAi(response?.data?.ICD10_Codes || []);
            console.log(response?.data?.ICD10_Codes);
        } catch (error) {
            console.error("Error fetching ai sugest:", error);
        } finally {
            setLoadingFetchingSugestKodeAI(false);
        }
    };

    useEffect(() => {
        fetchResume();
    }, []);

    return (
        <>
            <Card title="Resume Pasien" loading={loadingResume}>
                <table
                    style={{
                        width: "100%",
                        border: "1px solid #ccc",
                        borderCollapse: "collapse",
                    }}
                >
                    <tbody align="left">
                        <tr>
                            <th
                                style={{
                                    width: "25%",
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                            >
                                Anamnesa (S)
                            </th>
                            <td
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_ANAMNESA,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                            >
                                Pemeriksaan Fisik (O)
                            </th>
                            <td
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_CATATAN_FISIK,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                            >
                                Diagnosa (A)
                            </th>
                            <td
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_DIAGNOSA,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                            >
                                Tindakan/Planning (P)
                            </th>
                            <td
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_TINDAKAN,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                            >
                                Planning / Evaluasi
                            </th>
                            <td
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                                dangerouslySetInnerHTML={{
                                    __html: resumeData?.FS_PLANNING,
                                }}
                            ></td>
                        </tr>
                        <tr>
                            <th
                                style={{
                                    verticalAlign: "top",
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                            ></th>
                            <td
                                style={{
                                    border: "1px solid #ccc",
                                    padding: "8px",
                                }}
                            >
                                <Button
                                    color="purple"
                                    variant="solid"
                                    onClick={handleGetAiSugest}
                                >
                                    AI Sugest
                                </Button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </Card>

            <Modal
                title="Saran Kode Diagnosa dari AI"
                open={modalAiOpen}
                onOk={() => {}}
                onCancel={() => {
                    setModalAiOpen(false);
                }}
                okText={false}
                cancelText="Tidak"
                okButtonProps={{ danger: true }}
            >
                {loadingFetchingSugestKodeAI ? (
                    <>Berfikirr..Berfikirr..Berfikirr...</>
                ) : (
                    <>
                        {JSON.stringify(sugestDariAi)}
                        <table>
                            <tbody>
                                {sugestDariAi.map((item, index) => (
                                    <tr key={index}>
                                        <td>{item.Code}</td>
                                        <td>{item.Diagnosis}</td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </>
                )}
            </Modal>
        </>
    );
}
